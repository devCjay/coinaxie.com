<?php

namespace App\Http\Controllers\User\Trading;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
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

