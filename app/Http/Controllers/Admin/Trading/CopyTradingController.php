<?php

namespace App\Http\Controllers\Admin\Trading;

use App\Http\Controllers\Controller;
use App\Models\CopyTradingProTrader;
use App\Models\CopyTradingRelationship;
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

