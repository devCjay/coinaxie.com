<?php

namespace App\Http\Controllers\User\Trading;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
use App\Models\FuturesTradingOrders;
use App\Models\FuturesTradingPositions;
use App\Models\MarginTradingOrder;
use App\Models\MarginTradingPosition;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CopyTradingController extends Controller
{
    public function index()
    {
        $page_title = __('Copy Trading');
        $template = config('site.template');

        $activeLeaders = CopyTradingProTrader::query()
            ->where('status', 'active');

        $leadersCount = (int) $activeLeaders->count();

        $relationshipsCount = (int) CopyTradingRelationship::query()
            ->where('status', 'active')
            ->count();

        $topLeaders = CopyTradingProTrader::with('user')
            ->where('status', 'active')
            ->withCount(['relationships as followers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('followers_count')
            ->latest()
            ->take(3)
            ->get();

        $myPro = CopyTradingProTrader::query()
            ->where('user_id', auth()->id())
            ->first();

        $stats = [
            'leaders' => $leadersCount,
            'followers' => $relationshipsCount,
            'volume' => 0,
            'top_roi' => 0,
        ];

        $availableUsdt = (float) auth()->user()
            ->tradingAccounts()
            ->whereIn('account_type', ['futures', 'margin'])
            ->where('currency', 'USDT')
            ->sum('balance');

        $mode = 'landing';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'stats', 'topLeaders', 'myPro', 'availableUsdt'));
    }

    public function leaders()
    {
        $page_title = __('Copy Trading');
        $template = config('site.template');

        $pros = CopyTradingProTrader::with('user')
            ->where('status', 'active')
            ->withCount(['relationships as followers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->latest()
            ->get();

        $myRelationships = auth()->user()
            ->hasMany(CopyTradingRelationship::class, 'follower_id')
            ->get()
            ->keyBy('pro_trader_id');

        $myPro = CopyTradingProTrader::query()
            ->where('user_id', auth()->id())
            ->first();

        $availableUsdt = (float) auth()->user()
            ->tradingAccounts()
            ->whereIn('account_type', ['futures', 'margin'])
            ->where('currency', 'USDT')
            ->sum('balance');

        $mode = 'leaders';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'pros', 'myRelationships', 'myPro', 'availableUsdt'));
    }

    public function profile(int $id)
    {
        $page_title = __('Copy Trading');
        $template = config('site.template');

        $pro = CopyTradingProTrader::with('user')
            ->withCount(['relationships as followers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->where('status', 'active')
            ->findOrFail($id);

        $myRelationship = CopyTradingRelationship::query()
            ->where('pro_trader_id', $pro->id)
            ->where('follower_id', auth()->id())
            ->first();

        $userId = (int) $pro->user_id;

        $futuresTrades = FuturesTradingOrders::query()
            ->where('user_id', $userId)
            ->where('is_copy', false)
            ->where('status', 'filled')
            ->count();

        $marginTrades = MarginTradingOrder::query()
            ->where('user_id', $userId)
            ->where('is_copy', false)
            ->where('status', 'filled')
            ->count();

        $totalTrades = (int) ($futuresTrades + $marginTrades);

        $futuresProfit = (float) FuturesTradingPositions::query()
            ->where('user_id', $userId)
            ->sum('realized_pnl');

        $marginProfit = (float) MarginTradingPosition::query()
            ->where('user_id', $userId)
            ->sum('realized_pnl');

        $totalProfit = (float) ($futuresProfit + $marginProfit);

        $wins = (int) (FuturesTradingPositions::query()
            ->where('user_id', $userId)
            ->where('realized_pnl', '>', 0)
            ->count()
            + MarginTradingPosition::query()
                ->where('user_id', $userId)
                ->where('realized_pnl', '>', 0)
                ->count());

        $losses = (int) (FuturesTradingPositions::query()
            ->where('user_id', $userId)
            ->where('realized_pnl', '<', 0)
            ->count()
            + MarginTradingPosition::query()
                ->where('user_id', $userId)
                ->where('realized_pnl', '<', 0)
                ->count());

        $decisions = $wins + $losses;
        $winRate = $decisions > 0 ? ($wins / $decisions) * 100 : 0;

        $followers = (int) ($pro->followers_count ?? 0);
        $capacityMax = 100;

        $futuresPositions = FuturesTradingPositions::query()
            ->where('user_id', $userId)
            ->get();

        $marginPositions = MarginTradingPosition::query()
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->get();

        $futuresVolume = (float) FuturesTradingOrders::query()
            ->where('user_id', $userId)
            ->where('is_copy', false)
            ->where('status', 'filled')
            ->selectRaw('COALESCE(SUM(size * price), 0) as v')
            ->value('v');

        $marginVolume = (float) MarginTradingOrder::query()
            ->where('user_id', $userId)
            ->where('is_copy', false)
            ->where('status', 'filled')
            ->selectRaw('COALESCE(SUM(size * price), 0) as v')
            ->value('v');

        $usedMargin = (float) $futuresPositions->sum('margin') + (float) $marginPositions->sum('margin');
        $openPnl = 0.0;
        $minOpenPnl = null;
        $openWins = 0;
        $openLosses = 0;

        $allOpenPositions = $futuresPositions->concat($marginPositions);
        foreach ($allOpenPositions as $p) {
            $entry = (float) ($p->entry_price ?? 0);
            $mark = (float) ($p->current_price ?? 0);
            $size = (float) ($p->size ?? 0);
            $side = (string) ($p->side ?? 'buy');
            $pnl = (float) ($p->unrealized_pnl ?? 0);
            if ($pnl == 0.0 && $entry > 0 && $mark > 0 && $size > 0) {
                $pnl = $side === 'buy' ? (($mark - $entry) * $size) : (($entry - $mark) * $size);
            }
            $openPnl += $pnl;
            $minOpenPnl = $minOpenPnl === null ? $pnl : min($minOpenPnl, $pnl);
            if ($pnl > 0) {
                $openWins++;
            } elseif ($pnl < 0) {
                $openLosses++;
            }
        }

        $openDecisions = $openWins + $openLosses;
        $winRate = $openDecisions > 0 ? ($openWins / $openDecisions) * 100 : $winRate;
        $roi = $usedMargin > 0 ? ($openPnl / $usedMargin) * 100 : 0;
        $totalVolume = $futuresVolume + $marginVolume;
        $maxDrawdown = ($usedMargin > 0 && $minOpenPnl !== null && $minOpenPnl < 0) ? (abs($minOpenPnl) / $usedMargin) * 100 : null;

        $stats = [
            'roi' => $roi,
            'win_rate' => $winRate,
            'followers' => $followers,
            'total_trades' => $totalTrades,
            'total_profit' => $openPnl,
            'total_volume' => $totalVolume,
            'avg_profit_per_trade' => $totalTrades > 0 ? ($openPnl / $totalTrades) : 0,
            'max_drawdown' => $maxDrawdown,
        ];

        $tradeHistoryCollection = collect()
            ->concat($futuresPositions->map(function ($p) {
                $entry = (float) $p->entry_price;
                $mark = (float) $p->current_price;
                $size = (float) $p->size;
                $pnl = (float) $p->unrealized_pnl;
                if ($pnl == 0 && $entry > 0 && $mark > 0 && $size > 0) {
                    $pnl = $p->side === 'buy' ? (($mark - $entry) * $size) : (($entry - $mark) * $size);
                }
                $margin = (float) $p->margin;
                $roe = $margin > 0 ? ($pnl / $margin) * 100 : 0;
                return [
                    'market' => 'futures',
                    'ticker' => (string) $p->ticker,
                    'side' => (string) $p->side,
                    'size' => $size,
                    'entry_price' => $entry,
                    'mark_price' => $mark,
                    'pnl' => $pnl,
                    'roe' => $roe,
                    'leverage' => (float) $p->leverage,
                    'timestamp' => (int) $p->timestamp,
                ];
            }))
            ->concat($marginPositions->map(function ($p) {
                $entry = (float) $p->entry_price;
                $mark = (float) $p->current_price;
                $size = (float) $p->size;
                $pnl = (float) $p->unrealized_pnl;
                if ($pnl == 0 && $entry > 0 && $mark > 0 && $size > 0) {
                    $pnl = $p->side === 'buy' ? (($mark - $entry) * $size) : (($entry - $mark) * $size);
                }
                $margin = (float) $p->margin;
                $roe = $margin > 0 ? ($pnl / $margin) * 100 : 0;
                return [
                    'market' => 'margin',
                    'ticker' => (string) $p->ticker,
                    'side' => (string) $p->side,
                    'size' => $size,
                    'entry_price' => $entry,
                    'mark_price' => $mark,
                    'pnl' => $pnl,
                    'roe' => $roe,
                    'leverage' => (float) $p->leverage,
                    'timestamp' => (int) $p->timestamp,
                ];
            }))
            ->sortByDesc('timestamp')
            ->take(50)
            ->values();

        $perPage = 5;
        $page = (int) request('trades_page', 1);
        $page = max(1, $page);
        $total = (int) $tradeHistoryCollection->count();
        $items = $tradeHistoryCollection->slice(($page - 1) * $perPage, $perPage)->values();

        $tradeHistory = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'pageName' => 'trades_page',
        ]);
        $tradeHistory->setPath(url()->current());
        $tradeHistory->appends(request()->except('trades_page'));

        $profile = [
            'style' => $pro->style ?? 'SWING',
            'risk_level' => $pro->risk_level ?? 'Conservative',
            'profit_share_percent' => (float) ($pro->profit_share_percent ?? 0),
            'min_investment_amount' => (float) ($pro->min_investment_amount ?? 100),
            'min_investment_currency' => $pro->min_investment_currency ?? 'USDT',
            'capacity_max' => $capacityMax,
        ];

        $availableUsdt = (float) auth()->user()
            ->tradingAccounts()
            ->whereIn('account_type', ['futures', 'margin'])
            ->where('currency', 'USDT')
            ->sum('balance');

        $mode = 'profile';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'pro', 'myRelationship', 'stats', 'profile', 'availableUsdt', 'tradeHistory'));
    }

    public function requestLeader(Request $request)
    {
        $request->validate([
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
        ]);

        $existing = CopyTradingProTrader::query()
            ->where('user_id', auth()->id())
            ->first();

        if ($existing && $existing->status === 'active') {
            return response()->json([
                'status' => 'error',
                'message' => __('You are already a leader'),
            ], 422);
        }

        CopyTradingProTrader::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'display_name' => $request->display_name,
                'bio' => $request->bio,
                'status' => 'inactive',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => __('Leader request submitted. Awaiting admin approval.'),
        ]);
    }

    public function follow(Request $request)
    {
        $request->validate([
            'pro_trader_id' => 'required|exists:copy_trading_pro_traders,id',
            'amount' => 'nullable|numeric|min:0',
            'stop_loss_percent' => 'nullable|numeric|min:0|max:95',
            'market_type' => 'nullable|in:futures,margin,both',
            'allocation_type' => 'nullable|in:fixed,percent',
            'allocation_value' => 'nullable|numeric|min:0',
            'max_leverage' => 'nullable|integer|min:1|max:100',
            'margin_order_mode' => 'nullable|in:normal,borrow',
        ]);

        $amount = $request->filled('amount') ? (float) $request->amount : 0.0;
        $allocationType = $request->filled('amount') ? 'fixed' : (string) ($request->allocation_type ?? 'fixed');
        $allocationValue = $request->filled('amount') ? $amount : (float) ($request->allocation_value ?? 0);
        if ($allocationValue <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => __('Please enter an amount'),
            ], 422);
        }

        $minCopyAmount = (float) getSetting('copy_trading_min_amount', 10);
        if ($allocationType === 'fixed' && $minCopyAmount > 0 && $allocationValue < $minCopyAmount) {
            return response()->json([
                'status' => 'error',
                'message' => __('Minimum copy amount is :amount USDT', [
                    'amount' => number_format($minCopyAmount, 2, '.', ''),
                ]),
            ], 422);
        }

        $relationship = CopyTradingRelationship::updateOrCreate(
            [
                'pro_trader_id' => (int) $request->pro_trader_id,
                'follower_id' => auth()->id(),
            ],
            [
                'market_type' => (string) ($request->market_type ?? 'both'),
                'allocation_type' => $allocationType,
                'allocation_value' => $allocationValue,
                'stop_loss_percent' => $request->filled('stop_loss_percent') ? (float) $request->stop_loss_percent : null,
                'max_leverage' => (int) ($request->max_leverage ?? 50),
                'margin_order_mode' => (string) ($request->margin_order_mode ?? 'normal'),
                'status' => 'active',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => __('Copy trading enabled'),
            'relationship' => $relationship,
        ]);
    }

    public function unfollow(Request $request)
    {
        $request->validate([
            'pro_trader_id' => 'required|exists:copy_trading_pro_traders,id',
        ]);

        CopyTradingRelationship::where('pro_trader_id', (int) $request->pro_trader_id)
            ->where('follower_id', auth()->id())
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Copy trading disabled'),
        ]);
    }
}

