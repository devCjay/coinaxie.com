<?php

namespace App\Http\Controllers\Admin\Trading;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
use App\Models\FuturesTradingOrders;
use App\Models\FuturesTradingPositions;
use App\Models\MarginTradingOrder;
use App\Models\MarginTradingPosition;
use App\Services\CopyTradingService;
use App\Services\LozandServices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;

class CopyTradingController extends Controller
{
    public function pros()
    {
        $page_title = __('Copy Trading - Pro Traders');
        $template = config('site.template');
        $minCopyAmount = (float) getSetting('copy_trading_min_amount', 10);

        $pros = CopyTradingProTrader::with('user')
            ->withCount(['relationships as followers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->latest()
            ->get();

        $users = User::latest()->take(50)->get();

        $stats = [
            'pro_traders' => (int) $pros->count(),
            'active_pro_traders' => (int) $pros->where('status', 'active')->count(),
            'active_relationships' => (int) CopyTradingRelationship::query()->where('status', 'active')->count(),
            'total_followers' => (int) $pros->sum('followers_count'),
        ];

        return view("templates.{$template}.blades.admin.copy-trading.pros", compact('page_title', 'pros', 'users', 'minCopyAmount', 'stats'));
    }

    public function storePro(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'style' => 'nullable|string|max:50',
            'risk_level' => 'nullable|string|max:50',
            'profit_share_percent' => 'nullable|numeric|min:0|max:100',
            'min_investment_amount' => 'nullable|numeric|min:0',
            'min_investment_currency' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive',
        ]);

        CopyTradingProTrader::updateOrCreate(
            ['user_id' => (int) $request->user_id],
            [
                'display_name' => $request->display_name,
                'bio' => $request->bio,
                'style' => $request->style,
                'risk_level' => $request->risk_level,
                'profit_share_percent' => $request->profit_share_percent ?? 0,
                'min_investment_amount' => $request->min_investment_amount ?? 0,
                'min_investment_currency' => $request->min_investment_currency ?? 'USDT',
                'status' => $request->status,
            ]
        );

        return back()->with('success', __('Pro trader saved'));
    }

    public function updatePro(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:copy_trading_pro_traders,id',
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'style' => 'nullable|string|max:50',
            'risk_level' => 'nullable|string|max:50',
            'profit_share_percent' => 'nullable|numeric|min:0|max:100',
            'min_investment_amount' => 'nullable|numeric|min:0',
            'min_investment_currency' => 'nullable|string|max:10',
            'status' => 'required|in:active,inactive',
        ]);

        $pro = CopyTradingProTrader::findOrFail((int) $request->id);
        $pro->update([
            'display_name' => $request->display_name,
            'bio' => $request->bio,
            'style' => $request->style,
            'risk_level' => $request->risk_level,
            'profit_share_percent' => $request->profit_share_percent ?? 0,
            'min_investment_amount' => $request->min_investment_amount ?? 0,
            'min_investment_currency' => $request->min_investment_currency ?? 'USDT',
            'status' => $request->status,
        ]);

        return back()->with('success', __('Pro trader updated'));
    }

    public function deletePro(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:copy_trading_pro_traders,id',
        ]);

        $pro = CopyTradingProTrader::findOrFail((int) $request->id);
        $pro->delete();

        return back()->with('success', __('Pro trader deleted'));
    }

    public function updateMinAmount(Request $request)
    {
        $request->validate([
            'min_copy_amount' => 'required|numeric|min:0',
        ]);

        updateSetting('copy_trading_min_amount', (float) $request->min_copy_amount);

        return back()->with('success', __('Minimum copy amount updated'));
    }

    public function storeTradeHistory(Request $request)
    {
        $request->validate([
            'pro_trader_id' => 'required|exists:copy_trading_pro_traders,id',
            'market' => 'required|in:futures,margin',
            'type' => 'required|in:market,limit',
            'ticker' => 'required|string|max:30',
            'side' => 'required|in:buy,sell',
            'amount' => 'required|numeric|min:0.00000001',
            'leverage' => 'required|integer|min:1|max:100',
            'price' => 'nullable|numeric|min:0',
            'order_mode' => 'nullable|in:normal,borrow',
            'take_profit' => 'nullable|numeric|min:0',
            'stop_loss' => 'nullable|numeric|min:0',
            'pnl' => 'required|numeric',
        ]);

        $pro = CopyTradingProTrader::with('user')->findOrFail((int) $request->pro_trader_id);
        $user = $pro->user;
        if (!$user) {
            return back()->with('error', __('Trader not found'));
        }

        $market = (string) $request->market;
        $type = (string) $request->type;
        $ticker = (string) $request->ticker;
        $side = (string) $request->side;
        $leverage = (int) $request->leverage;
        $quoteAmount = (float) $request->amount;
        $tp = (float) ($request->take_profit ?? 0);
        $sl = (float) ($request->stop_loss ?? 0);

        $lozandServices = new LozandServices();
        $tickerInfo = $market === 'futures' ? $lozandServices->futureTicker($ticker) : $lozandServices->margin($ticker);
        if (($tickerInfo['status'] ?? null) !== 'success') {
            return back()->with('error', __('Failed to fetch market data'));
        }

        $currentPrice = (float) ($tickerInfo['data']['current_price'] ?? 0);
        $entryPrice = $type === 'market' ? $currentPrice : (float) ($request->price ?? 0);
        if ($entryPrice <= 0) {
            return back()->with('error', __('Invalid entry price'));
        }

        $pnlOverride = (float) $request->pnl;

        if ($tp > 0 && $side === 'buy' && $tp <= $entryPrice) {
            return back()->with('error', __('Take profit should be greater than entry price'));
        }
        if ($tp > 0 && $side === 'sell' && $tp >= $entryPrice) {
            return back()->with('error', __('Take profit should be less than entry price'));
        }
        if ($sl > 0 && $side === 'buy' && $sl >= $entryPrice) {
            return back()->with('error', __('Stop loss should be less than entry price'));
        }
        if ($sl > 0 && $side === 'sell' && $sl <= $entryPrice) {
            return back()->with('error', __('Stop loss should be greater than entry price'));
        }

        if ($type === 'limit' && $entryPrice <= 0) {
            return back()->with('error', __('Price is required for limit orders'));
        }

        $baseAmount = $quoteAmount / $entryPrice;
        if ($baseAmount <= 0) {
            return back()->with('error', __('Invalid trade amount'));
        }

        if ($type === 'market' && $pnlOverride !== null) {
            $markPrice = $entryPrice;
            if ($side === 'buy') {
                $markPrice = $entryPrice + ($pnlOverride / $baseAmount);
            } else {
                $markPrice = $entryPrice - ($pnlOverride / $baseAmount);
            }
            if ($markPrice > 0) {
                $currentPrice = $markPrice;
            }
        }

        try {
            if ($market === 'futures') {
                $tradingAccount = $user->tradingAccounts()
                    ->where('account_type', 'futures')
                    ->where('currency', 'USDT')
                    ->first() ?? $user->tradingAccounts()->where('account_type', 'futures')->first();

                if (!$tradingAccount) {
                    return back()->with('error', __('Futures trading account not found'));
                }

                $position = FuturesTradingPositions::where('user_id', $user->id)->where('ticker', $ticker)->first();

                $lockedMargin = 0;
                if ($position && $position->side !== $side) {
                    if ($baseAmount > (float) $position->size) {
                        $excess = $baseAmount - (float) $position->size;
                        $lockedMargin = ($excess * $entryPrice) / $leverage;
                    }
                } else {
                    $lockedMargin = ($baseAmount * $entryPrice) / $leverage;
                }

                if ($lockedMargin > 0 && (float) $tradingAccount->balance < $lockedMargin) {
                    return back()->with('error', __('Insufficient balance for margin'));
                }

                DB::transaction(function () use ($user, $tradingAccount, $ticker, $side, $type, $entryPrice, $currentPrice, $baseAmount, $lockedMargin, $leverage, $tp, $sl) {
                    $order = FuturesTradingOrders::create([
                        'user_id' => $user->id,
                        'type' => $type,
                        'ticker' => $ticker,
                        'side' => $side,
                        'size' => $baseAmount,
                        'price' => $entryPrice,
                        'take_profit' => $tp > 0 ? $tp : null,
                        'stop_loss' => $sl > 0 ? $sl : null,
                        'locked_margin' => $lockedMargin,
                        'leverage' => $leverage,
                        'status' => $type === 'market' ? 'filled' : 'pending',
                        'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                        'timestamp' => (string) now()->valueOf(),
                    ]);

                    DB::afterCommit(function () use ($order) {
                        app(CopyTradingService::class)->handleFuturesOrderCreated($order->fresh());
                    });

                    if ($type === 'market') {
                        $position = FuturesTradingPositions::where('user_id', $user->id)->where('ticker', $ticker)->first();
                        if ($position) {
                            if ($position->side === $side) {
                                if ($lockedMargin > 0) {
                                    $tradingAccount->decrement('balance', $lockedMargin);
                                }
                                $totalSize = (float) $position->size + $baseAmount;
                                $newEntryPrice = (((float) $position->entry_price * (float) $position->size) + ($entryPrice * $baseAmount)) / $totalSize;
                                $position->update([
                                    'size' => $totalSize,
                                    'entry_price' => $newEntryPrice,
                                    'current_price' => $currentPrice,
                                    'margin' => (float) $position->margin + $lockedMargin,
                                    'take_profit' => $tp > 0 ? $tp : 0,
                                    'stop_loss' => $sl > 0 ? $sl : 0,
                                    'unrealized_pnl' => 0,
                                    'realized_pnl' => 0,
                                    'timestamp' => (string) now()->valueOf(),
                                ]);
                            } else {
                                if ((float) $position->size > $baseAmount) {
                                    $marginToRefund = ((float) $position->margin / (float) $position->size) * $baseAmount;
                                    $tradingAccount->increment('balance', $marginToRefund);
                                    $position->update([
                                        'size' => (float) $position->size - $baseAmount,
                                        'current_price' => $currentPrice,
                                        'margin' => (float) $position->margin - $marginToRefund,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                } elseif ((float) $position->size == $baseAmount) {
                                    $tradingAccount->increment('balance', (float) $position->margin);
                                    $position->delete();
                                } else {
                                    $tradingAccount->increment('balance', (float) $position->margin);
                                    if ($lockedMargin > 0) {
                                        $tradingAccount->decrement('balance', $lockedMargin);
                                    }
                                    $remaining = $baseAmount - (float) $position->size;
                                    $position->update([
                                        'side' => $side,
                                        'size' => $remaining,
                                        'entry_price' => $entryPrice,
                                        'current_price' => $currentPrice,
                                        'margin' => $lockedMargin,
                                        'take_profit' => $tp > 0 ? $tp : 0,
                                        'stop_loss' => $sl > 0 ? $sl : 0,
                                        'unrealized_pnl' => 0,
                                        'realized_pnl' => 0,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                }
                            }
                        } else {
                            if ($lockedMargin > 0) {
                                $tradingAccount->decrement('balance', $lockedMargin);
                            }
                            FuturesTradingPositions::create([
                                'user_id' => $user->id,
                                'ticker' => $ticker,
                                'side' => $side,
                                'size' => $baseAmount,
                                'entry_price' => $entryPrice,
                                'current_price' => $currentPrice,
                                'margin' => $lockedMargin,
                                'leverage' => $leverage,
                                'take_profit' => $tp > 0 ? $tp : 0,
                                'stop_loss' => $sl > 0 ? $sl : 0,
                                'unrealized_pnl' => 0,
                                'realized_pnl' => 0,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        }
                    } else {
                        if ($lockedMargin > 0) {
                            $tradingAccount->decrement('balance', $lockedMargin);
                        }
                    }
                });
                if ($type === 'market') {
                    // First set the unrealized PnL
                    FuturesTradingPositions::where('user_id', $user->id)->where('ticker', $ticker)->update([
                        'current_price' => $currentPrice,
                        'unrealized_pnl' => $pnlOverride,
                        'timestamp' => (string) now()->valueOf(),
                    ]);

                    // Now close the position and realize the PnL!
                    $position = FuturesTradingPositions::where('user_id', $user->id)->where('ticker', $ticker)->first();
                    if ($position) {
                        // Refund margin
                        $tradingAccount->increment('balance', (float) $position->margin);

                        // Add PnL to balance!
                        $tradingAccount->increment('balance', $pnlOverride);
                        $tradingAccount->refresh();

                        // Mark position as realized, set realized_pnl!
                        $position->update([
                            'unrealized_pnl' => 0,
                            'realized_pnl' => $pnlOverride,
                            'timestamp' => (string) now()->valueOf(),
                        ]);

                        // Record transaction for pro trader
                        $ref = \Str::random(12);
                        $typeTx = $pnlOverride > 0 ? 'credit' : 'debit';
                        $absPnl = abs($pnlOverride);
                        $proDesc = "Futures trading PnL on {$ticker}: " . ($pnlOverride > 0 ? "+" : "") . number_format($pnlOverride, 2) . " USDT";
                        recordTransaction($user, $absPnl, 'USDT', $absPnl, 'USDT', 1, $typeTx, 'completed', $ref, $proDesc, $tradingAccount->balance);

                        // Send notification and email to pro trader
                        $proTitle = $pnlOverride > 0 ? "Futures Trading Profit" : "Futures Trading Loss";
                        $proBody = __("You have realized a PnL of :pnl USDT on your futures trade for :ticker.", [
                            'pnl' => number_format($pnlOverride, 2),
                            'ticker' => $ticker
                        ], $user->lang);
                        recordNotificationMessage($user, $proTitle, $proBody);
                        sendRichTextEmail($proTitle, nl2br(e($proBody)), $user);

                        // Now do the same for followers!
                        $relationships = CopyTradingRelationship::where('pro_trader_id', $pro->id)
                            ->where('status', 'active')
                            ->whereIn('market_type', ['futures', 'both'])
                            ->get();

                        foreach ($relationships as $rel) {
                            try {
                                $follower = $rel->follower;
                                if (!$follower || $follower->id === $user->id) continue;

                                $followerAccount = $follower->tradingAccounts()
                                    ->where('account_type', 'futures')
                                    ->where('currency', 'USDT')
                                    ->first() ?? $follower->tradingAccounts()->where('account_type', 'futures')->first();
                                if (!$followerAccount) continue;

                                // Calculate follower's PnL based on their allocation
                                $followerPnl = 0;
                                $totalProBalance = (float) $tradingAccount->balance;
                                if ($totalProBalance > 0) {
                                    $followerRatio = 0;
                                    if ($rel->allocation_type === 'percent') {
                                        $followerRatio = ((float) $rel->allocation_value) / 100;
                                    } else {
                                        $followerRatio = ((float) $rel->allocation_value) / $totalProBalance;
                                    }
                                    $followerRatio = min(1, max(0, $followerRatio));
                                    $followerPnl = $pnlOverride * $followerRatio;
                                } else {
                                    $followerPnl = $pnlOverride * 0.1;
                                }

                                // Create a copy position for follower, then close it!
                                $followerPos = FuturesTradingPositions::where('user_id', $follower->id)->where('ticker', $ticker)->first();

                                // If follower has open pos, close it and credit/debit balance
                                if ($followerPos) {
                                    $followerAccount->increment('balance', (float) $followerPos->margin);
                                    $followerAccount->increment('balance', $followerPnl);
                                    $followerPos->update([
                                        'unrealized_pnl' => 0,
                                        'realized_pnl' => $followerPnl,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                } else {
                                    // Just credit/debit the follower's balance
                                    $followerAccount->increment('balance', $followerPnl);
                                }
                                $followerAccount->refresh();

                                // Record transaction for follower
                                $followerRef = \Str::random(12);
                                $followerTypeTx = $followerPnl > 0 ? 'credit' : 'debit';
                                $followerAbsPnl = abs($followerPnl);
                                $followerDesc = "Copy trading PnL ({$pro->display_name}) on {$ticker}: " . ($followerPnl > 0 ? "+" : "") . number_format($followerPnl, 2) . " USDT";
                                recordTransaction($follower, $followerAbsPnl, 'USDT', $followerAbsPnl, 'USDT', 1, $followerTypeTx, 'completed', $followerRef, $followerDesc, $followerAccount->balance);

                                // Send notification and email to follower
                                $followerTitle = $followerPnl > 0 ? "Copy Trading Profit" : "Copy Trading Loss";
                                $followerBody = __("You have realized a PnL of :pnl USDT from copying :pro_trader's futures trade for :ticker.", [
                                    'pnl' => number_format($followerPnl, 2),
                                    'pro_trader' => $pro->display_name,
                                    'ticker' => $ticker
                                ], $follower->lang);
                                recordNotificationMessage($follower, $followerTitle, $followerBody);
                                sendRichTextEmail($followerTitle, nl2br(e($followerBody)), $follower);
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                        }
                    }
                }
            } else {
                $orderMode = (string) ($request->order_mode ?? 'normal');
                $tradingAccount = $user->tradingAccounts()
                    ->where('account_type', 'margin')
                    ->where('currency', 'USDT')
                    ->first() ?? $user->tradingAccounts()->where('account_type', 'margin')->first();

                if (!$tradingAccount) {
                    return back()->with('error', __('Margin trading account not found'));
                }

                $lockedMargin = ($baseAmount * $entryPrice) / $leverage;

                if ($orderMode === 'borrow' && (float) $tradingAccount->balance < $lockedMargin) {
                    $borrowAmount = $lockedMargin - (float) $tradingAccount->balance;
                    $tradingAccount->increment('borrowed', (float) $borrowAmount);
                    $tradingAccount->increment('balance', (float) $borrowAmount);
                }

                if ((float) $tradingAccount->balance < $lockedMargin) {
                    return back()->with('error', __('Insufficient balance for margin'));
                }

                DB::transaction(function () use ($user, $tradingAccount, $orderMode, $ticker, $side, $type, $entryPrice, $currentPrice, $baseAmount, $lockedMargin, $leverage, $tp, $sl) {
                    $order = MarginTradingOrder::create([
                        'user_id' => $user->id,
                        'type' => $type,
                        'order_mode' => $orderMode,
                        'ticker' => $ticker,
                        'side' => $side,
                        'size' => $baseAmount,
                        'price' => $entryPrice,
                        'locked_margin' => $lockedMargin,
                        'leverage' => $leverage,
                        'take_profit' => $tp > 0 ? $tp : null,
                        'stop_loss' => $sl > 0 ? $sl : null,
                        'status' => $type === 'market' ? 'filled' : 'pending',
                        'timestamp' => (string) now()->valueOf(),
                    ]);

                    DB::afterCommit(function () use ($order) {
                        app(CopyTradingService::class)->handleMarginOrderCreated($order->fresh());
                    });

                    if ($type === 'market') {
                        $tradingAccount->decrement('balance', (float) $lockedMargin);
                        $position = MarginTradingPosition::where('user_id', $user->id)->where('ticker', $ticker)->first();
                        if ($position) {
                            if ($position->side === $side) {
                                $totalSize = (float) $position->size + $baseAmount;
                                $newEntryPrice = (((float) $position->entry_price * (float) $position->size) + ($entryPrice * $baseAmount)) / $totalSize;
                                $position->update([
                                    'size' => $totalSize,
                                    'entry_price' => $newEntryPrice,
                                    'current_price' => $currentPrice,
                                    'margin' => (float) $position->margin + $lockedMargin,
                                    'timestamp' => (string) now()->valueOf(),
                                ]);
                            } else {
                                if ((float) $position->size > $baseAmount) {
                                    $marginToRefund = ((float) $position->margin / (float) $position->size) * $baseAmount;
                                    $tradingAccount->increment('balance', (float) $marginToRefund);
                                    $position->update([
                                        'size' => (float) $position->size - $baseAmount,
                                        'current_price' => $currentPrice,
                                        'margin' => (float) $position->margin - $marginToRefund,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                } elseif ((float) $position->size == $baseAmount) {
                                    $tradingAccount->increment('balance', (float) $position->margin);
                                    $position->delete();
                                } else {
                                    $tradingAccount->increment('balance', (float) $position->margin);
                                    $remaining = $baseAmount - (float) $position->size;
                                    $position->update([
                                        'side' => $side,
                                        'size' => $remaining,
                                        'entry_price' => $entryPrice,
                                        'current_price' => $currentPrice,
                                        'margin' => $lockedMargin,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                }
                            }
                        } else {
                            MarginTradingPosition::create([
                                'user_id' => $user->id,
                                'ticker' => $ticker,
                                'side' => $side,
                                'size' => $baseAmount,
                                'entry_price' => $entryPrice,
                                'current_price' => $currentPrice,
                                'margin' => $lockedMargin,
                                'leverage' => $leverage,
                                'timestamp' => (string) now()->valueOf(),
                            ]);
                        }
                    } else {
                        $tradingAccount->decrement('balance', (float) $lockedMargin);
                    }
                });
                if ($type === 'market') {
                    // First set the unrealized PnL
                    MarginTradingPosition::where('user_id', $user->id)->where('ticker', $ticker)->where('status', 'open')->update([
                        'current_price' => $currentPrice,
                        'unrealized_pnl' => $pnlOverride,
                        'timestamp' => (string) now()->valueOf(),
                    ]);

                    $position = MarginTradingPosition::where('user_id', $user->id)->where('ticker', $ticker)->where('status', 'open')->first();
                    if ($position) {
                        // Refund margin and add PnL!
                        $tradingAccount->increment('balance', (float) $position->margin);
                        $tradingAccount->increment('balance', $pnlOverride);
                        $tradingAccount->refresh();

                        $position->update([
                            'unrealized_pnl' => 0,
                            'realized_pnl' => $pnlOverride,
                            'timestamp' => (string) now()->valueOf(),
                        ]);

                        // Record transaction for pro trader
                        $ref = \Str::random(12);
                        $typeTx = $pnlOverride > 0 ? 'credit' : 'debit';
                        $absPnl = abs($pnlOverride);
                        $proDesc = "Margin trading PnL on {$ticker}: " . ($pnlOverride > 0 ? "+" : "") . number_format($pnlOverride, 2) . " USDT";
                        recordTransaction($user, $absPnl, 'USDT', $absPnl, 'USDT', 1, $typeTx, 'completed', $ref, $proDesc, $tradingAccount->balance);

                        // Send notification and email to pro trader
                        $proTitle = $pnlOverride > 0 ? "Margin Trading Profit" : "Margin Trading Loss";
                        $proBody = __("You have realized a PnL of :pnl USDT on your margin trade for :ticker.", [
                            'pnl' => number_format($pnlOverride, 2),
                            'ticker' => $ticker
                        ], $user->lang);
                        recordNotificationMessage($user, $proTitle, $proBody);
                        sendRichTextEmail($proTitle, nl2br(e($proBody)), $user);

                        $relationships = CopyTradingRelationship::where('pro_trader_id', $pro->id)
                            ->where('status', 'active')
                            ->whereIn('market_type', ['margin', 'both'])
                            ->get();

                        foreach ($relationships as $rel) {
                            try {
                                $follower = $rel->follower;
                                if (!$follower || $follower->id === $user->id) continue;

                                $followerAccount = $follower->tradingAccounts()
                                    ->where('account_type', 'margin')
                                    ->where('currency', 'USDT')
                                    ->first() ?? $follower->tradingAccounts()->where('account_type', 'margin')->first();
                                if (!$followerAccount) continue;

                                $followerPnl = 0;
                                $totalProBalance = (float) $tradingAccount->balance;
                                if ($totalProBalance > 0) {
                                    $followerRatio = 0;
                                    if ($rel->allocation_type === 'percent') {
                                        $followerRatio = ((float) $rel->allocation_value) / 100;
                                    } else {
                                        $followerRatio = ((float) $rel->allocation_value) / $totalProBalance;
                                    }
                                    $followerRatio = min(1, max(0, $followerRatio));
                                    $followerPnl = $pnlOverride * $followerRatio;
                                } else {
                                    $followerPnl = $pnlOverride * 0.1;
                                }

                                $followerPos = MarginTradingPosition::where('user_id', $follower->id)->where('ticker', $ticker)->where('status', 'open')->first();
                                if ($followerPos) {
                                    $followerAccount->increment('balance', (float) $followerPos->margin);
                                    $followerAccount->increment('balance', $followerPnl);
                                    $followerPos->update([
                                        'unrealized_pnl' => 0,
                                        'realized_pnl' => $followerPnl,
                                        'timestamp' => (string) now()->valueOf(),
                                    ]);
                                } else {
                                    $followerAccount->increment('balance', $followerPnl);
                                }
                                $followerAccount->refresh();

                                // Record transaction for follower
                                $followerRef = \Str::random(12);
                                $followerTypeTx = $followerPnl > 0 ? 'credit' : 'debit';
                                $followerAbsPnl = abs($followerPnl);
                                $followerDesc = "Copy trading PnL ({$pro->display_name}) on {$ticker}: " . ($followerPnl > 0 ? "+" : "") . number_format($followerPnl, 2) . " USDT";
                                recordTransaction($follower, $followerAbsPnl, 'USDT', $followerAbsPnl, 'USDT', 1, $followerTypeTx, 'completed', $followerRef, $followerDesc, $followerAccount->balance);

                                // Send notification and email to follower
                                $followerTitle = $followerPnl > 0 ? "Copy Trading Profit" : "Copy Trading Loss";
                                $followerBody = __("You have realized a PnL of :pnl USDT from copying :pro_trader's margin trade for :ticker.", [
                                    'pnl' => number_format($followerPnl, 2),
                                    'pro_trader' => $pro->display_name,
                                    'ticker' => $ticker
                                ], $follower->lang);
                                recordNotificationMessage($follower, $followerTitle, $followerBody);
                                sendRichTextEmail($followerTitle, nl2br(e($followerBody)), $follower);
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                        }
                    }
                }
            }

            return back()->with('success', __('Trade history added'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function relationships()
    {
        $page_title = __('Copy Trading - Relationships');
        $template = config('site.template');

        $relationships = CopyTradingRelationship::with(['proTrader.user', 'follower'])
            ->latest()
            ->paginate(50);

        return view("templates.{$template}.blades.admin.copy-trading.relationships", compact('page_title', 'relationships'));
    }
}

