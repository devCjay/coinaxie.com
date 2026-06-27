<?php

namespace App\Services;

use App\Models\LaunchpadMarket;
use App\Models\LaunchpadOrder;
use App\Models\LaunchpadTrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LaunchpadMatchingService
{
    public function __construct(
        protected LaunchpadWalletService $wallet
    ) {
    }

    public function placeOrder(User $user, LaunchpadMarket $market, array $payload): LaunchpadOrder
    {
        $side = $payload['side'];
        $type = $payload['type'];
        $price = $payload['price'] ?? null;
        $quoteBudget = (float) ($payload['quote_amount'] ?? 0);
        $baseAmount = (float) ($payload['base_amount'] ?? 0);

        if (!in_array($side, ['buy', 'sell'], true) || !in_array($type, ['limit', 'market'], true)) {
            throw new \InvalidArgumentException('Invalid order');
        }

        if ($market->status !== 'active') {
            throw new \RuntimeException('Market inactive');
        }

        if ($type === 'limit') {
            $price = (float) $price;
            if ($price <= 0) {
                throw new \InvalidArgumentException('Invalid price');
            }
        }

        return DB::transaction(function () use ($user, $market, $side, $type, $price, $quoteBudget, $baseAmount) {
            $timestamp = (string) now()->valueOf();
            $quoteCurrency = $market->quote_currency;
            $baseCurrency = $market->base_currency;

            if ($side === 'buy') {
                if ($quoteBudget <= 0) {
                    throw new \InvalidArgumentException('Invalid amount');
                }
            } else {
                if ($baseAmount <= 0) {
                    throw new \InvalidArgumentException('Invalid amount');
                }
            }

            if ($type === 'market') {
                if ($side === 'buy') {
                    return $this->executeMarketBuy($user, $market, $quoteCurrency, $baseCurrency, $quoteBudget, $timestamp);
                }
                return $this->executeMarketSell($user, $market, $quoteCurrency, $baseCurrency, $baseAmount, $timestamp);
            }

            if ($side === 'buy') {
                $baseQty = $quoteBudget / (float) $price;
                if ($baseQty <= 0) {
                    throw new \InvalidArgumentException('Invalid amount');
                }

                $lockedQuote = $baseQty * (float) $price;
                $this->wallet->debitSpot($user, $quoteCurrency, (float) $lockedQuote);

                $order = LaunchpadOrder::create([
                    'market_id' => $market->id,
                    'user_id' => $user->id,
                    'side' => 'buy',
                    'type' => 'limit',
                    'price' => (float) $price,
                    'base_qty' => (float) $baseQty,
                    'filled_base_qty' => 0,
                    'locked_quote' => (float) $lockedQuote,
                    'locked_base' => 0,
                    'status' => 'pending',
                    'timestamp' => $timestamp,
                ]);

                $this->matchLimitOrder($market, $order);
                return $order->fresh();
            }

            $this->wallet->debitSpot($user, $baseCurrency, (float) $baseAmount);
            $order = LaunchpadOrder::create([
                'market_id' => $market->id,
                'user_id' => $user->id,
                'side' => 'sell',
                'type' => 'limit',
                'price' => (float) $price,
                'base_qty' => (float) $baseAmount,
                'filled_base_qty' => 0,
                'locked_quote' => 0,
                'locked_base' => (float) $baseAmount,
                'status' => 'pending',
                'timestamp' => $timestamp,
            ]);

            $this->matchLimitOrder($market, $order);
            return $order->fresh();
        });
    }

    public function cancelOrder(User $user, LaunchpadOrder $order): LaunchpadOrder
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new \RuntimeException('Unauthorized');
        }
        if (!in_array($order->status, ['pending', 'partially_filled'], true)) {
            return $order;
        }

        return DB::transaction(function () use ($user, $order) {
            $order->refresh();
            if (!in_array($order->status, ['pending', 'partially_filled'], true)) {
                return $order;
            }

            $remainingBase = (float) $order->base_qty - (float) $order->filled_base_qty;
            if ($remainingBase < 0) {
                $remainingBase = 0;
            }

            if ($order->side === 'buy') {
                $remainingQuote = (float) $order->locked_quote;
                if ($remainingQuote > 0) {
                    $this->wallet->creditSpot($user, $order->market->quote_currency, $remainingQuote);
                }
            } else {
                $remainingBaseLocked = (float) $order->locked_base;
                if ($remainingBaseLocked > 0) {
                    $this->wallet->creditSpot($user, $order->market->base_currency, $remainingBaseLocked);
                }
            }

            $order->update([
                'status' => 'canceled',
                'locked_quote' => 0,
                'locked_base' => 0,
            ]);

            return $order->fresh();
        });
    }

    protected function executeMarketBuy(User $user, LaunchpadMarket $market, string $quoteCurrency, string $baseCurrency, float $quoteBudget, string $timestamp): LaunchpadOrder
    {
        $this->wallet->debitSpot($user, $quoteCurrency, $quoteBudget);

        $order = LaunchpadOrder::create([
            'market_id' => $market->id,
            'user_id' => $user->id,
            'side' => 'buy',
            'type' => 'market',
            'price' => null,
            'base_qty' => 0,
            'filled_base_qty' => 0,
            'locked_quote' => $quoteBudget,
            'locked_base' => 0,
            'status' => 'pending',
            'timestamp' => $timestamp,
        ]);

        $remainingQuote = $quoteBudget;
        $totalBaseFilled = 0.0;

        $sellOrders = LaunchpadOrder::where('market_id', $market->id)
            ->where('side', 'sell')
            ->whereIn('status', ['pending', 'partially_filled'])
            ->orderBy('price', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($sellOrders as $sell) {
            if ($remainingQuote <= 0) {
                break;
            }

            $sellPrice = (float) $sell->price;
            if ($sellPrice <= 0) {
                continue;
            }

            $sellRemainingBase = (float) $sell->base_qty - (float) $sell->filled_base_qty;
            if ($sellRemainingBase <= 0) {
                continue;
            }

            $maxBaseAtThisPrice = $remainingQuote / $sellPrice;
            $tradeBase = min($sellRemainingBase, $maxBaseAtThisPrice);
            if ($tradeBase <= 0) {
                continue;
            }

            $tradeQuote = $tradeBase * $sellPrice;
            $this->applyTrade($market, $sell, $order, $sellPrice, $tradeBase, $tradeQuote, 'buy');

            $remainingQuote -= $tradeQuote;
            $totalBaseFilled += $tradeBase;
        }

        $order->refresh();
        $refund = (float) $order->locked_quote;
        if ($refund > 0) {
            $this->wallet->creditSpot($user, $quoteCurrency, $refund);
        }

        $order->update([
            'base_qty' => $totalBaseFilled,
            'filled_base_qty' => $totalBaseFilled,
            'locked_quote' => 0,
            'status' => $totalBaseFilled > 0 ? 'filled' : 'canceled',
        ]);

        return $order->fresh();
    }

    protected function executeMarketSell(User $user, LaunchpadMarket $market, string $quoteCurrency, string $baseCurrency, float $baseAmount, string $timestamp): LaunchpadOrder
    {
        $this->wallet->debitSpot($user, $baseCurrency, $baseAmount);

        $order = LaunchpadOrder::create([
            'market_id' => $market->id,
            'user_id' => $user->id,
            'side' => 'sell',
            'type' => 'market',
            'price' => null,
            'base_qty' => $baseAmount,
            'filled_base_qty' => 0,
            'locked_quote' => 0,
            'locked_base' => $baseAmount,
            'status' => 'pending',
            'timestamp' => $timestamp,
        ]);

        $remainingBase = $baseAmount;
        $totalBaseFilled = 0.0;

        $buyOrders = LaunchpadOrder::where('market_id', $market->id)
            ->where('side', 'buy')
            ->whereIn('status', ['pending', 'partially_filled'])
            ->orderBy('price', 'desc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($buyOrders as $buy) {
            if ($remainingBase <= 0) {
                break;
            }

            $buyPrice = (float) $buy->price;
            if ($buyPrice <= 0) {
                continue;
            }

            $buyRemainingBase = (float) $buy->base_qty - (float) $buy->filled_base_qty;
            if ($buyRemainingBase <= 0) {
                continue;
            }

            $tradeBase = min($buyRemainingBase, $remainingBase);
            if ($tradeBase <= 0) {
                continue;
            }

            $tradeQuote = $tradeBase * $buyPrice;
            $this->applyTrade($market, $order, $buy, $buyPrice, $tradeBase, $tradeQuote, 'sell');

            $remainingBase -= $tradeBase;
            $totalBaseFilled += $tradeBase;
        }

        $order->refresh();
        $refundBase = (float) $order->locked_base;
        if ($refundBase > 0) {
            $this->wallet->creditSpot($user, $baseCurrency, $refundBase);
        }

        $order->update([
            'filled_base_qty' => $totalBaseFilled,
            'locked_base' => 0,
            'status' => $totalBaseFilled > 0 ? 'filled' : 'canceled',
        ]);

        return $order->fresh();
    }

    protected function matchLimitOrder(LaunchpadMarket $market, LaunchpadOrder $order): void
    {
        $order->refresh();
        if (!in_array($order->status, ['pending', 'partially_filled'], true)) {
            return;
        }

        if ($order->side === 'buy') {
            $sellOrders = LaunchpadOrder::where('market_id', $market->id)
                ->where('side', 'sell')
                ->whereIn('status', ['pending', 'partially_filled'])
                ->where('price', '<=', (float) $order->price)
                ->orderBy('price', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingBaseNeed = (float) $order->base_qty - (float) $order->filled_base_qty;

            foreach ($sellOrders as $sell) {
                if ($remainingBaseNeed <= 0) {
                    break;
                }

                $sellRemainingBase = (float) $sell->base_qty - (float) $sell->filled_base_qty;
                if ($sellRemainingBase <= 0) {
                    continue;
                }

                $tradePrice = (float) $sell->price;
                if ($tradePrice <= 0) {
                    continue;
                }

                $order->refresh();
                $quoteAffordableBase = (float) $order->locked_quote / $tradePrice;
                if ($quoteAffordableBase <= 0) {
                    break;
                }

                $tradeBase = min($sellRemainingBase, $remainingBaseNeed, $quoteAffordableBase);
                if ($tradeBase <= 0) {
                    continue;
                }

                $tradeQuote = $tradeBase * $tradePrice;

                $this->applyTrade($market, $sell, $order, $tradePrice, $tradeBase, $tradeQuote, 'buy');
                $remainingBaseNeed -= $tradeBase;
            }

            $order->refresh();
            $filled = (float) $order->filled_base_qty;
            $status = $filled >= (float) $order->base_qty ? 'filled' : ($filled > 0 ? 'partially_filled' : 'pending');

            if ($status === 'filled') {
                $refund = (float) $order->locked_quote;
                if ($refund > 0) {
                    $this->wallet->creditSpot($order->user, $market->quote_currency, $refund);
                }
                $order->update([
                    'locked_quote' => 0,
                    'status' => 'filled',
                ]);
            } else {
                $order->update([
                    'status' => $status,
                ]);
            }

            return;
        }

        $buyOrders = LaunchpadOrder::where('market_id', $market->id)
            ->where('side', 'buy')
            ->whereIn('status', ['pending', 'partially_filled'])
            ->where('price', '>=', (float) $order->price)
            ->orderBy('price', 'desc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingBaseSell = (float) $order->base_qty - (float) $order->filled_base_qty;
        $received = 0.0;

        foreach ($buyOrders as $buy) {
            if ($remainingBaseSell <= 0) {
                break;
            }

            $buyRemainingBase = (float) $buy->base_qty - (float) $buy->filled_base_qty;
            if ($buyRemainingBase <= 0) {
                continue;
            }

            $tradeBase = min($buyRemainingBase, $remainingBaseSell);
            if ($tradeBase <= 0) {
                continue;
            }

            $tradePrice = (float) $buy->price;
            $tradeQuote = $tradeBase * $tradePrice;

            $this->applyTrade($market, $order, $buy, $tradePrice, $tradeBase, $tradeQuote, 'sell');
            $received += $tradeQuote;
            $remainingBaseSell -= $tradeBase;
        }

        $order->refresh();
        $filled = (float) $order->filled_base_qty;
        $status = ($filled >= (float) $order->base_qty) ? 'filled' : ($filled > 0 ? 'partially_filled' : 'pending');
        $order->update([
            'status' => $status,
        ]);
    }

    protected function applyTrade(LaunchpadMarket $market, LaunchpadOrder $sellOrder, LaunchpadOrder $buyOrder, float $price, float $baseQty, float $quoteQty, string $takerSide): void
    {
        $sellOrder->refresh();
        $buyOrder->refresh();

        $sellFilled = (float) $sellOrder->filled_base_qty + $baseQty;
        $buyFilled = (float) $buyOrder->filled_base_qty + $baseQty;

        $sellStatus = $sellFilled >= (float) $sellOrder->base_qty ? 'filled' : 'partially_filled';
        $buyStatus = $buyOrder->type === 'market'
            ? ($buyFilled > 0 ? 'partially_filled' : 'pending')
            : ($buyFilled >= (float) $buyOrder->base_qty ? 'filled' : 'partially_filled');

        $sellOrder->update([
            'filled_base_qty' => $sellFilled,
            'status' => $sellStatus,
            'locked_base' => $sellStatus === 'filled' ? 0 : max(0, (float) $sellOrder->base_qty - $sellFilled),
        ]);

        $buyOrderLocked = (float) $buyOrder->locked_quote;
        $buyOrder->update([
            'filled_base_qty' => $buyFilled,
            'status' => $buyStatus,
            'locked_quote' => max(0, $buyOrderLocked - $quoteQty),
        ]);

        $this->wallet->creditSpot($sellOrder->user, $market->quote_currency, $quoteQty);
        $this->wallet->creditSpot($buyOrder->user, $market->base_currency, $baseQty);

        LaunchpadTrade::create([
            'market_id' => $market->id,
            'maker_order_id' => $takerSide === 'buy' ? $sellOrder->id : $buyOrder->id,
            'taker_order_id' => $takerSide === 'buy' ? $buyOrder->id : $sellOrder->id,
            'maker_user_id' => $takerSide === 'buy' ? $sellOrder->user_id : $buyOrder->user_id,
            'taker_user_id' => $takerSide === 'buy' ? $buyOrder->user_id : $sellOrder->user_id,
            'price' => $price,
            'base_qty' => $baseQty,
            'quote_qty' => $quoteQty,
            'taker_side' => $takerSide,
            'timestamp' => (string) now()->valueOf(),
        ]);

        $market->update([
            'last_price' => $price,
        ]);
    }
}
