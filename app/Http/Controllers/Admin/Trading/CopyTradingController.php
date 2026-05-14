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

        $pros = CopyTradingProTrader::with('user')
            ->withCount(['relationships as followers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->latest()
            ->get();

        $users = User::latest()->take(50)->get();

        return view("templates.{$template}.blades.admin.copy-trading.pros", compact('page_title', 'pros', 'users'));
    }

    public function storePro(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive',
        ]);

        CopyTradingProTrader::updateOrCreate(
            ['user_id' => (int) $request->user_id],
            [
                'display_name' => $request->display_name,
                'bio' => $request->bio,
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
            'status' => 'required|in:active,inactive',
        ]);

        $pro = CopyTradingProTrader::findOrFail((int) $request->id);
        $pro->update([
            'display_name' => $request->display_name,
            'bio' => $request->bio,
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

