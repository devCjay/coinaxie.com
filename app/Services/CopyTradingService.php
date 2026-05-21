<?php

namespace App\Services;

use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
use App\Models\FuturesTradingOrders;
use App\Models\FuturesTradingPositions;
use App\Models\MarginTradingOrder;
use App\Models\MarginTradingPosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CopyTradingService
{
    public function __construct(protected LozandServices $lozandServices)
    {
    }

    public function handleFuturesOrderCreated(FuturesTradingOrders $order): void
    {
        if ($order->is_copy) {
            return;
        }

        $pro = CopyTradingProTrader::where('user_id', $order->user_id)->where('status', 'active')->first();
        if (!$pro) {
            return;
        }

        $relationships = CopyTradingRelationship::where('pro_trader_id', $pro->id)
            ->where('status', 'active')
            ->whereIn('market_type', ['futures', 'both'])
            ->get();

        foreach ($relationships as $relationship) {
            try {
                $this->copyFuturesOrderToFollower($order, $relationship);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public function handleMarginOrderCreated(MarginTradingOrder $order): void
    {
        if ($order->is_copy) {
            return;
        }

        $pro = CopyTradingProTrader::where('user_id', $order->user_id)->where('status', 'active')->first();
        if (!$pro) {
            return;
        }

        $relationships = CopyTradingRelationship::where('pro_trader_id', $pro->id)
            ->where('status', 'active')
            ->whereIn('market_type', ['margin', 'both'])
            ->get();

        foreach ($relationships as $relationship) {
            try {
                $this->copyMarginOrderToFollower($order, $relationship);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public function handleFuturesOrderCanceled(FuturesTradingOrders $order): void
    {
        $copiedOrders = FuturesTradingOrders::where('is_copy', true)
            ->where('copied_from_user_id', $order->user_id)
            ->where('copied_from_order_id', $order->id)
            ->where('status', 'pending')
            ->get();

        foreach ($copiedOrders as $copied) {
            DB::transaction(function () use ($copied) {
                $trading_account = User::find($copied->user_id)
                    ?->tradingAccounts()
                    ->where('account_type', 'futures')
                    ->first();

                if ($trading_account && (float) $copied->locked_margin > 0) {
                    $trading_account->increment('balance', (float) $copied->locked_margin);
                }

                $copied->update(['status' => 'canceled']);
            });
        }
    }

    public function handleMarginOrderCanceled(MarginTradingOrder $order): void
    {
        $copiedOrders = MarginTradingOrder::where('is_copy', true)
            ->where('copied_from_user_id', $order->user_id)
            ->where('copied_from_order_id', $order->id)
            ->where('status', 'pending')
            ->get();

        foreach ($copiedOrders as $copied) {
            DB::transaction(function () use ($copied) {
                $trading_account = User::find($copied->user_id)
                    ?->tradingAccounts()
                    ->where('account_type', 'margin')
                    ->first();

                if ($trading_account && (float) $copied->locked_margin > 0) {
                    $refund_amount = (float) $copied->locked_margin;
                    if ((float) $trading_account->borrowed > 0) {
                        $to_repay = min((float) $trading_account->borrowed, $refund_amount);
                        $trading_account->decrement('borrowed', (float) $to_repay);
                        $refund_amount -= $to_repay;
                    }
                    if ($refund_amount > 0) {
                        $trading_account->increment('balance', $refund_amount);
                    }
                }

                $copied->update(['status' => 'canceled']);
            });
        }
    }

    protected function copyFuturesOrderToFollower(FuturesTradingOrders $source, CopyTradingRelationship $relationship): void
    {
        $follower = $relationship->follower;
        if (!$follower || $follower->id === $source->user_id) {
            return;
        }

        $account = $follower->tradingAccounts()->where('account_type', 'futures')->first();
        if (!$account || $account->account_status !== 'active') {
            return;
        }

        $ticker_info = $this->lozandServices->futureTicker($source->ticker);
        if (!is_array($ticker_info) || ($ticker_info['status'] ?? null) !== 'success') {
            return;
        }

        $current_price = (float) ($ticker_info['data']['current_price'] ?? 0);
        if ($current_price <= 0) {
            return;
        }

        $isCloseSignal = $source->type === 'market' && $source->status === 'filled' && (float) $source->locked_margin <= 0;
        $leverage = max(1, min((int) $source->leverage, (int) $relationship->max_leverage));

        if ($isCloseSignal) {
            $position = FuturesTradingPositions::where('user_id', $follower->id)->where('ticker', $source->ticker)->first();
            if (!$position) {
                return;
            }
            if ($position->side === $source->side) {
                return;
            }

            $quote_amount = 0.0;
            $proPosition = FuturesTradingPositions::where('user_id', $source->user_id)->where('ticker', $source->ticker)->first();
            if ($proPosition) {
                $proOldSize = (float) $proPosition->size + (float) $source->size;
                $fraction = $proOldSize > 0 ? ((float) $source->size / $proOldSize) : 1.0;
                $fraction = max(0.0, min(1.0, $fraction));
                $closeBase = (float) $position->size * $fraction;
                $quote_amount = $closeBase * $current_price;
            } else {
                $quote_amount = (float) $position->size * $current_price;
            }
            if ($quote_amount <= 0) {
                return;
            }

            $copied = $this->executeFuturesForUser($follower, [
                'ticker' => $source->ticker,
                'type' => 'market',
                'side' => $source->side,
                'amount' => $quote_amount,
                'price' => null,
                'leverage' => $leverage,
                'take_profit' => (float) ($source->take_profit ?? 0),
                'stop_loss' => 0,
            ], [
                'is_copy' => true,
                'copied_from_user_id' => $source->user_id,
                'copied_from_order_id' => $source->id,
                'copy_relationship_id' => $relationship->id,
            ]);
            if ($copied) {
                $this->notifyCopyTrade($source, $relationship, $follower, $copied, 'futures', 'close');
            }
            return;
        }

        $target_quote = $this->calculateFollowerQuoteAmount((float) $account->balance, $relationship);
        if ($target_quote <= 0) {
            return;
        }

        $entryRef = $source->type === 'market' ? $current_price : (float) $source->price;
        $stopLoss = $this->resolveStopLossPrice((float) ($source->stop_loss ?? 0), $relationship, $entryRef, (string) $source->side);

        $copied = $this->executeFuturesForUser($follower, [
            'ticker' => $source->ticker,
            'type' => $source->type,
            'side' => $source->side,
            'amount' => $target_quote,
            'price' => $source->type === 'limit' ? (float) $source->price : null,
            'leverage' => $leverage,
            'take_profit' => (float) ($source->take_profit ?? 0),
            'stop_loss' => $stopLoss,
        ], [
            'is_copy' => true,
            'copied_from_user_id' => $source->user_id,
            'copied_from_order_id' => $source->id,
            'copy_relationship_id' => $relationship->id,
        ]);
        if ($copied) {
            $this->notifyCopyTrade($source, $relationship, $follower, $copied, 'futures', 'open');
        }
    }

    protected function copyMarginOrderToFollower(MarginTradingOrder $source, CopyTradingRelationship $relationship): void
    {
        $follower = $relationship->follower;
        if (!$follower || $follower->id === $source->user_id) {
            return;
        }

        $account = $follower->tradingAccounts()->where('account_type', 'margin')->first();
        if (!$account || $account->account_status !== 'active') {
            return;
        }

        $ticker_info = $this->lozandServices->margin($source->ticker);
        if (!is_array($ticker_info) || ($ticker_info['status'] ?? null) !== 'success') {
            return;
        }

        $current_price = (float) ($ticker_info['data']['current_price'] ?? 0);
        if ($current_price <= 0) {
            return;
        }

        $isCloseSignal = $source->type === 'market' && $source->status === 'filled' && (float) $source->locked_margin <= 0;
        $leverage = max(1, min((int) $source->leverage, (int) $relationship->max_leverage));

        if ($isCloseSignal) {
            $position = MarginTradingPosition::where('user_id', $follower->id)->where('ticker', $source->ticker)->first();
            if (!$position) {
                return;
            }
            if ($position->side === $source->side) {
                return;
            }

            $quote_amount = 0.0;
            $proPosition = MarginTradingPosition::where('user_id', $source->user_id)->where('ticker', $source->ticker)->first();
            if ($proPosition) {
                $proOldSize = (float) $proPosition->size + (float) $source->size;
                $fraction = $proOldSize > 0 ? ((float) $source->size / $proOldSize) : 1.0;
                $fraction = max(0.0, min(1.0, $fraction));
                $closeBase = (float) $position->size * $fraction;
                $quote_amount = $closeBase * $current_price;
            } else {
                $quote_amount = (float) $position->size * $current_price;
            }
            if ($quote_amount <= 0) {
                return;
            }

            $copied = $this->executeMarginForUser($follower, [
                'ticker' => $source->ticker,
                'type' => 'market',
                'side' => $source->side,
                'amount' => $quote_amount,
                'price' => null,
                'leverage' => $leverage,
                'order_mode' => $relationship->margin_order_mode,
                'take_profit' => (float) ($source->take_profit ?? 0),
                'stop_loss' => 0,
            ], [
                'is_copy' => true,
                'copied_from_user_id' => $source->user_id,
                'copied_from_order_id' => $source->id,
                'copy_relationship_id' => $relationship->id,
            ]);
            if ($copied) {
                $this->notifyCopyTrade($source, $relationship, $follower, $copied, 'margin', 'close');
            }
            return;
        }

        $target_quote = $this->calculateFollowerQuoteAmount((float) $account->balance, $relationship);
        if ($target_quote <= 0) {
            return;
        }

        $entryRef = $source->type === 'market' ? $current_price : (float) $source->price;
        $stopLoss = $this->resolveStopLossPrice((float) ($source->stop_loss ?? 0), $relationship, $entryRef, (string) $source->side);

        $copied = $this->executeMarginForUser($follower, [
            'ticker' => $source->ticker,
            'type' => $source->type,
            'side' => $source->side,
            'amount' => $target_quote,
            'price' => $source->type === 'limit' ? (float) $source->price : null,
            'leverage' => $leverage,
            'order_mode' => $relationship->margin_order_mode,
            'take_profit' => (float) ($source->take_profit ?? 0),
            'stop_loss' => $stopLoss,
        ], [
            'is_copy' => true,
            'copied_from_user_id' => $source->user_id,
            'copied_from_order_id' => $source->id,
            'copy_relationship_id' => $relationship->id,
        ]);
        if ($copied) {
            $this->notifyCopyTrade($source, $relationship, $follower, $copied, 'margin', 'open');
        }
    }

    protected function notifyCopyTrade($sourceOrder, CopyTradingRelationship $relationship, User $follower, $copiedOrder, string $market, string $action): void
    {
        try {
            $leader = User::find((int) $sourceOrder->user_id);
            if (!$leader) {
                return;
            }

            $leaderName = $leader->username ?? $leader->email ?? __('Leader');
            $followerName = $follower->username ?? $follower->email ?? __('Follower');
            $ticker = strtoupper((string) ($sourceOrder->ticker ?? $copiedOrder->ticker ?? ''));
            $side = strtoupper((string) ($sourceOrder->side ?? $copiedOrder->side ?? ''));
            $type = strtoupper((string) ($copiedOrder->type ?? $sourceOrder->type ?? ''));
            $status = strtoupper((string) ($copiedOrder->status ?? ''));
            $quote = 0.0;
            if (isset($copiedOrder->size, $copiedOrder->price)) {
                $quote = (float) $copiedOrder->size * (float) $copiedOrder->price;
            }
            $quoteText = $quote > 0 ? number_format($quote, 2) . ' USDT' : '';
            $marketText = $market === 'margin' ? __('Margin') : __('Futures');
            $actionText = $action === 'close' ? __('Close') : __('Open');

            $titleFollower = $status === 'FILLED' ? __('Copy trade executed') : __('Copy trade placed');
            $bodyFollower = __('Leader :leader :action a :market trade (:type) on :ticker (:side).', [
                'leader' => $leaderName,
                'action' => strtolower((string) $actionText),
                'market' => strtolower((string) $marketText),
                'type' => $type,
                'ticker' => $ticker,
                'side' => $side,
            ]);
            if ($quoteText !== '') {
                $bodyFollower .= ' ' . __('Amount: :amount', ['amount' => $quoteText]);
            }

            recordNotificationMessage($follower, $titleFollower, $bodyFollower);
            sendRichTextEmail($titleFollower, nl2br(e($bodyFollower)), $follower);

            $titleLeader = $status === 'FILLED' ? __('Trade copied') : __('Copy order placed');
            $bodyLeader = __('Your :market trade (:type) on :ticker (:side) was copied for follower :follower.', [
                'market' => strtolower((string) $marketText),
                'type' => $type,
                'ticker' => $ticker,
                'side' => $side,
                'follower' => $followerName,
            ]);
            if ($quoteText !== '') {
                $bodyLeader .= ' ' . __('Amount: :amount', ['amount' => $quoteText]);
            }

            recordNotificationMessage($leader, $titleLeader, $bodyLeader);
            sendRichTextEmail($titleLeader, nl2br(e($bodyLeader)), $leader);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function resolveStopLossPrice(float $sourceStopLoss, CopyTradingRelationship $relationship, float $entryPrice, string $side): float
    {
        $pct = (float) ($relationship->stop_loss_percent ?? 0);
        if ($pct <= 0 || $entryPrice <= 0) {
            return $sourceStopLoss;
        }

        $pct = max(0, min($pct, 95));
        if ($side === 'sell') {
            return $entryPrice * (1 + ($pct / 100));
        }

        return max(0, $entryPrice * (1 - ($pct / 100)));
    }

    protected function calculateFollowerQuoteAmount(float $availableBalance, CopyTradingRelationship $relationship): float
    {
        $value = (float) $relationship->allocation_value;
        if ($value <= 0) {
            return 0;
        }

        if ($relationship->allocation_type === 'fixed') {
            return min($value, $availableBalance);
        }

        return max(0, $availableBalance * ($value / 100));
    }

    protected function executeFuturesForUser(User $user, array $data, array $copyMeta = []): ?FuturesTradingOrders
    {
        $trading_account = $user->tradingAccounts()->where('account_type', 'futures')->first();
        if (!$trading_account || $trading_account->account_status !== 'active') {
            return null;
        }

        $ticker = (string) $data['ticker'];
        $type = (string) $data['type'];
        $side = (string) $data['side'];
        $leverage = (int) ($data['leverage'] ?? 1);
        $leverage = max(1, $leverage);

        $ticker_info = $this->lozandServices->futureTicker($ticker);
        if (!is_array($ticker_info) || ($ticker_info['status'] ?? null) !== 'success') {
            return null;
        }

        $current_price = (float) ($ticker_info['data']['current_price'] ?? 0);
        $entry_price = $type === 'market' ? $current_price : (float) ($data['price'] ?? 0);
        if ($entry_price <= 0) {
            return null;
        }

        $quote_amount = (float) ($data['amount'] ?? 0);
        if ($quote_amount <= 0) {
            return null;
        }

        $base_amount = $quote_amount / $entry_price;

        $position = FuturesTradingPositions::where('user_id', $user->id)
            ->where('ticker', $ticker)
            ->first();

        $locked_margin = 0.0;
        if ($position && $position->side !== $side) {
            if ((float) $base_amount > (float) $position->size) {
                $excess_base_amount = (float) $base_amount - (float) $position->size;
                $locked_margin = ($excess_base_amount * $entry_price) / $leverage;
            }
        } else {
            $locked_margin = ((float) $base_amount * $entry_price) / $leverage;
        }

        if ($locked_margin > 0 && (float) $trading_account->balance < $locked_margin) {
            $max_base = ((float) $trading_account->balance * $leverage) / $entry_price;
            if ($max_base <= 0) {
                return null;
            }
            $base_amount = min((float) $base_amount, $max_base);
            $locked_margin = ($base_amount * $entry_price) / $leverage;
            $quote_amount = $base_amount * $entry_price;
        }

        return DB::transaction(function () use ($user, $trading_account, $ticker, $type, $side, $leverage, $entry_price, $current_price, $base_amount, $position, $locked_margin, $data, $copyMeta) {
            $order = FuturesTradingOrders::create([
                'user_id' => $user->id,
                'type' => $type,
                'ticker' => $ticker,
                'side' => $side,
                'size' => (float) $base_amount,
                'price' => (float) $entry_price,
                'take_profit' => (float) ($data['take_profit'] ?? 0),
                'stop_loss' => (float) ($data['stop_loss'] ?? 0),
                'locked_margin' => (float) $locked_margin,
                'leverage' => (int) $leverage,
                'status' => $type === 'market' ? 'filled' : 'pending',
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'is_copy' => (bool) ($copyMeta['is_copy'] ?? false),
                'copied_from_user_id' => $copyMeta['copied_from_user_id'] ?? null,
                'copied_from_order_id' => $copyMeta['copied_from_order_id'] ?? null,
                'copy_relationship_id' => $copyMeta['copy_relationship_id'] ?? null,
                'timestamp' => (string) now()->valueOf(),
            ]);

            if ($type === 'market') {
                if ($position) {
                    if ($position->side === $side) {
                        if ($locked_margin > 0) {
                            $trading_account->decrement('balance', (float) $locked_margin);
                        }
                        $total_size = (float) $position->size + (float) $base_amount;
                        $new_entry_price = (((float) $position->entry_price * (float) $position->size) + ($entry_price * (float) $base_amount)) / $total_size;
                        $position->update([
                            'size' => $total_size,
                            'entry_price' => $new_entry_price,
                            'current_price' => $current_price,
                            'margin' => (float) $position->margin + (float) $locked_margin,
                            'take_profit' => (float) ($data['take_profit'] ?? 0),
                            'stop_loss' => (float) ($data['stop_loss'] ?? 0),
                            'unrealized_pnl' => 0,
                            'realized_pnl' => 0,
                            'timestamp' => (string) now()->valueOf(),
                        ]);
                    } else {
                        if ((float) $position->size > (float) $base_amount) {
                            $margin_to_refund = ((float) $position->margin / (float) $position->size) * (float) $base_amount;
                            $trading_account->increment('balance', $margin_to_refund);
                            $position->update([
                                'size' => (float) $position->size - (float) $base_amount,
                                'current_price' => $current_price,
                                'margin' => (float) $position->margin - $margin_to_refund,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        } elseif ((float) $position->size == (float) $base_amount) {
                            $trading_account->increment('balance', (float) $position->margin);
                            $position->delete();
                        } else {
                            $trading_account->increment('balance', (float) $position->margin);
                            if ($locked_margin > 0) {
                                $trading_account->decrement('balance', (float) $locked_margin);
                            }
                            $remaining_base_amount = (float) $base_amount - (float) $position->size;
                            $position->update([
                                'side' => $side,
                                'size' => $remaining_base_amount,
                                'entry_price' => $entry_price,
                                'current_price' => $current_price,
                                'margin' => (float) $locked_margin,
                                'take_profit' => (float) ($data['take_profit'] ?? 0),
                                'stop_loss' => (float) ($data['stop_loss'] ?? 0),
                                'unrealized_pnl' => 0,
                                'realized_pnl' => 0,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        }
                    }
                } else {
                    if ($locked_margin > 0) {
                        $trading_account->decrement('balance', (float) $locked_margin);
                    }
                    FuturesTradingPositions::create([
                        'user_id' => $user->id,
                        'ticker' => $ticker,
                        'side' => $side,
                        'size' => (float) $base_amount,
                        'entry_price' => $entry_price,
                        'current_price' => $current_price,
                        'margin' => (float) $locked_margin,
                        'leverage' => $leverage,
                        'take_profit' => (float) ($data['take_profit'] ?? 0),
                        'stop_loss' => (float) ($data['stop_loss'] ?? 0),
                        'unrealized_pnl' => 0,
                        'realized_pnl' => 0,
                        'timestamp' => (string) now()->valueOf(),
                    ]);
                }
            } else {
                if ($locked_margin > 0) {
                    $trading_account->decrement('balance', (float) $locked_margin);
                }
            }

            return $order;
        });
    }

    protected function executeMarginForUser(User $user, array $data, array $copyMeta = []): ?MarginTradingOrder
    {
        $trading_account = $user->tradingAccounts()->where('account_type', 'margin')->first();
        if (!$trading_account || $trading_account->account_status !== 'active') {
            return null;
        }

        $ticker = (string) $data['ticker'];
        $type = (string) $data['type'];
        $side = (string) $data['side'];
        $leverage = (int) ($data['leverage'] ?? 1);
        $leverage = max(1, $leverage);
        $order_mode = (string) ($data['order_mode'] ?? 'normal');
        if (!in_array($order_mode, ['normal', 'borrow'], true)) {
            $order_mode = 'normal';
        }

        $ticker_info = $this->lozandServices->margin($ticker);
        if (!is_array($ticker_info) || ($ticker_info['status'] ?? null) !== 'success') {
            return null;
        }

        $current_price = (float) ($ticker_info['data']['current_price'] ?? 0);
        $entry_price = $type === 'market' ? $current_price : (float) ($data['price'] ?? 0);
        if ($entry_price <= 0) {
            return null;
        }

        $quote_amount = (float) ($data['amount'] ?? 0);
        if ($quote_amount <= 0) {
            return null;
        }

        $base_amount = $quote_amount / $entry_price;
        $locked_margin = ((float) $base_amount * $entry_price) / $leverage;

        if ($order_mode === 'borrow' && (float) $trading_account->balance < $locked_margin) {
            $borrow_amount = $locked_margin - (float) $trading_account->balance;
            $trading_account->increment('borrowed', (float) $borrow_amount);
            $trading_account->increment('balance', (float) $borrow_amount);
        }

        if ((float) $trading_account->balance < $locked_margin) {
            $max_base = ((float) $trading_account->balance * $leverage) / $entry_price;
            if ($max_base <= 0) {
                return null;
            }
            $base_amount = min((float) $base_amount, $max_base);
            $locked_margin = ($base_amount * $entry_price) / $leverage;
            $quote_amount = $base_amount * $entry_price;
        }

        return DB::transaction(function () use ($user, $trading_account, $ticker, $type, $side, $leverage, $entry_price, $current_price, $base_amount, $locked_margin, $order_mode, $data, $copyMeta) {
            $order = MarginTradingOrder::create([
                'user_id' => $user->id,
                'type' => $type,
                'order_mode' => $order_mode,
                'is_copy' => (bool) ($copyMeta['is_copy'] ?? false),
                'copied_from_user_id' => $copyMeta['copied_from_user_id'] ?? null,
                'copied_from_order_id' => $copyMeta['copied_from_order_id'] ?? null,
                'copy_relationship_id' => $copyMeta['copy_relationship_id'] ?? null,
                'ticker' => $ticker,
                'side' => $side,
                'size' => (float) $base_amount,
                'price' => (float) $entry_price,
                'locked_margin' => (float) $locked_margin,
                'leverage' => (int) $leverage,
                'take_profit' => (float) ($data['take_profit'] ?? 0),
                'stop_loss' => (float) ($data['stop_loss'] ?? 0),
                'status' => $type === 'market' ? 'filled' : 'pending',
                'timestamp' => (string) now()->valueOf(),
            ]);

            if ($type === 'market') {
                $trading_account->decrement('balance', (float) $locked_margin);

                $position = MarginTradingPosition::where('user_id', $user->id)
                    ->where('ticker', $ticker)
                    ->first();

                if ($position) {
                    if ($position->side === $side) {
                        $total_size = (float) $position->size + (float) $base_amount;
                        $new_entry_price = (((float) $position->entry_price * (float) $position->size) + ($entry_price * (float) $base_amount)) / $total_size;
                        $position->update([
                            'size' => $total_size,
                            'entry_price' => $new_entry_price,
                            'current_price' => $current_price,
                            'margin' => (float) $position->margin + (float) $locked_margin,
                            'timestamp' => (string) now()->valueOf(),
                        ]);
                    } else {
                        if ((float) $position->size > (float) $base_amount) {
                            $margin_to_refund = ((float) $position->margin / (float) $position->size) * (float) $base_amount;
                            $trading_account->increment('balance', (float) $margin_to_refund);
                            $position->update([
                                'size' => (float) $position->size - (float) $base_amount,
                                'current_price' => $current_price,
                                'margin' => (float) $position->margin - $margin_to_refund,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        } elseif ((float) $position->size == (float) $base_amount) {
                            $trading_account->increment('balance', (float) $position->margin);
                            $position->delete();
                        } else {
                            $trading_account->increment('balance', (float) $position->margin);
                            $remaining_base_amount = (float) $base_amount - (float) $position->size;
                            $position->update([
                                'side' => $side,
                                'size' => $remaining_base_amount,
                                'entry_price' => $entry_price,
                                'current_price' => $current_price,
                                'margin' => (float) $locked_margin,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        }
                    }
                } else {
                    MarginTradingPosition::create([
                        'user_id' => $user->id,
                        'ticker' => $ticker,
                        'side' => $side,
                        'size' => (float) $base_amount,
                        'entry_price' => $entry_price,
                        'current_price' => $current_price,
                        'margin' => (float) $locked_margin,
                        'leverage' => $leverage,
                        'unrealized_pnl' => 0,
                        'realized_pnl' => 0,
                        'timestamp' => (string) now()->valueOf(),
                        'status' => 'open',
                    ]);
                }
            } else {
                $trading_account->decrement('balance', (float) $locked_margin);
            }

            return $order;
        });
    }
}

