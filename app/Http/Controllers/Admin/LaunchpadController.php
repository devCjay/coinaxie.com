<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaunchpadController extends Controller
{
    public function index()
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $projects = LaunchpadProject::latest()->paginate(30);

        return view("templates.{$template}.blades.admin.launchpad.index", compact('page_title', 'projects'));
    }

    public function store(Request $request, LaunchpadService $launchpad)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token_symbol' => 'required|string|max:32',
            'token_name' => 'nullable|string|max:255',
            'token_decimals' => 'nullable|integer|min:0|max:18',
            'token_logo_url' => 'nullable|string|max:2048',
            'description' => 'nullable|string|max:10000',
            'quote_currency' => 'required|string|max:16',
            'sale_price' => 'required|numeric|min:0.00000001',
            'hard_cap_quote' => 'nullable|numeric|min:0',
            'min_buy_quote' => 'nullable|numeric|min:0',
            'max_buy_quote' => 'nullable|numeric|min:0',
            'sale_start_at' => 'nullable|date',
            'sale_end_at' => 'nullable|date',
            'launch_at' => 'nullable|date',
        ]);

        $slug = Str::slug($request->name . '-' . strtoupper($request->token_symbol));
        if (LaunchpadProject::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(6);
        }

        $project = LaunchpadProject::create([
            'slug' => $slug,
            'name' => $request->name,
            'token_symbol' => strtoupper($request->token_symbol),
            'token_name' => $request->token_name,
            'token_decimals' => $request->token_decimals ?? 8,
            'token_logo_url' => $request->token_logo_url,
            'description' => $request->description,
            'quote_currency' => strtoupper($request->quote_currency),
            'sale_price' => (float) $request->sale_price,
            'hard_cap_quote' => (float) ($request->hard_cap_quote ?? 0),
            'min_buy_quote' => (float) ($request->min_buy_quote ?? 0),
            'max_buy_quote' => (float) ($request->max_buy_quote ?? 0),
            'sale_start_at' => $request->sale_start_at,
            'sale_end_at' => $request->sale_end_at,
            'launch_at' => $request->launch_at,
            'status' => 'draft',
            'trading_enabled' => false,
        ]);

        $launchpad->ensureMarket($project);

        return back()->with('success', __('Launchpad project created'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:launchpad_projects,id',
            'name' => 'required|string|max:255',
            'token_name' => 'nullable|string|max:255',
            'token_decimals' => 'nullable|integer|min:0|max:18',
            'token_logo_url' => 'nullable|string|max:2048',
            'description' => 'nullable|string|max:10000',
            'quote_currency' => 'required|string|max:16',
            'sale_price' => 'required|numeric|min:0.00000001',
            'hard_cap_quote' => 'nullable|numeric|min:0',
            'min_buy_quote' => 'nullable|numeric|min:0',
            'max_buy_quote' => 'nullable|numeric|min:0',
            'sale_start_at' => 'nullable|date',
            'sale_end_at' => 'nullable|date',
            'launch_at' => 'nullable|date',
            'status' => 'required|in:draft,live,ended,launched,canceled',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->id);
        $project->update([
            'name' => $request->name,
            'token_name' => $request->token_name,
            'token_decimals' => $request->token_decimals ?? $project->token_decimals,
            'token_logo_url' => $request->token_logo_url,
            'description' => $request->description,
            'quote_currency' => strtoupper($request->quote_currency),
            'sale_price' => (float) $request->sale_price,
            'hard_cap_quote' => (float) ($request->hard_cap_quote ?? 0),
            'min_buy_quote' => (float) ($request->min_buy_quote ?? 0),
            'max_buy_quote' => (float) ($request->max_buy_quote ?? 0),
            'sale_start_at' => $request->sale_start_at,
            'sale_end_at' => $request->sale_end_at,
            'launch_at' => $request->launch_at,
            'status' => $request->status,
        ]);

        return back()->with('success', __('Launchpad project updated'));
    }

    public function finalize(Request $request, LaunchpadService $launchpad)
    {
        $request->validate([
            'id' => 'required|integer|exists:launchpad_projects,id',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->id);
        $launchpad->finalizeSale($project);

        return back()->with('success', __('Sale finalized and tokens allocated'));
    }

    public function enableTrading(Request $request, LaunchpadService $launchpad)
    {
        $request->validate([
            'id' => 'required|integer|exists:launchpad_projects,id',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->id);
        $launchpad->enableTrading($project);

        return back()->with('success', __('Trading enabled'));
    }
}

