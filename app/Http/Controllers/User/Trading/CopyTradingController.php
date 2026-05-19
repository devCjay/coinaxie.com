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

        $mode = 'landing';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'stats', 'topLeaders', 'myPro'));
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

        $mode = 'leaders';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'pros', 'myRelationships'));
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

        $stats = [
            'roi' => 0,
            'win_rate' => $winRate,
            'followers' => $followers,
            'total_trades' => $totalTrades,
            'total_profit' => $totalProfit,
            'total_volume' => 0,
            'avg_profit_per_trade' => $totalTrades > 0 ? ($totalProfit / $totalTrades) : 0,
            'max_drawdown' => null,
        ];

        $profile = [
            'style' => 'SWING',
            'risk_level' => 'Conservative',
            'profit_share_percent' => 0,
            'min_investment_amount' => 100,
            'min_investment_currency' => 'USDT',
            'capacity_max' => $capacityMax,
        ];

        $mode = 'profile';

        return view("templates.{$template}.blades.user.trading.copy_trading", compact('page_title', 'mode', 'pro', 'myRelationship', 'stats', 'profile'));
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
            'market_type' => 'required|in:futures,margin,both',
            'allocation_type' => 'required|in:fixed,percent',
            'allocation_value' => 'required|numeric|min:0',
            'max_leverage' => 'required|integer|min:1|max:100',
            'margin_order_mode' => 'required|in:normal,borrow',
        ]);

        $relationship = CopyTradingRelationship::updateOrCreate(
            [
                'pro_trader_id' => (int) $request->pro_trader_id,
                'follower_id' => auth()->id(),
            ],
            [
                'market_type' => $request->market_type,
                'allocation_type' => $request->allocation_type,
                'allocation_value' => (float) $request->allocation_value,
                'max_leverage' => (int) $request->max_leverage,
                'margin_order_mode' => $request->margin_order_mode,
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

