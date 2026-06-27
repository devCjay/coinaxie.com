<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\TradingAccount;

class LaunchpadController extends Controller
{
    public function index()
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $search = request('search');
        $sort = request('sort', 'created_at');
        $dir = request('dir', 'desc');

        $allowedSort = ['created_at', 'sold_quote', 'sold_tokens', 'sale_price', 'name', 'status'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }
        $dir = strtolower((string) $dir) === 'asc' ? 'asc' : 'desc';

        $query = LaunchpadProject::query()
            ->when($search, function ($q) use ($search) {
                $term = (string) $search;
                return $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', "%{$term}%")
                        ->orWhere('token_symbol', 'like', "%{$term}%")
                        ->orWhere('quote_currency', 'like', "%{$term}%")
                        ->orWhere('status', 'like', "%{$term}%");
                });
            });

        $allProjects = (clone $query)->get(['id', 'token_symbol', 'quote_currency', 'status', 'trading_enabled', 'sold_quote', 'sold_tokens']);
        $tokenSymbols = $allProjects->pluck('token_symbol')->map(fn ($s) => strtoupper((string) $s))->unique()->values()->all();

        $stats = [
            'unique_tokens' => (int) $allProjects->pluck('token_symbol')->unique()->count(),
            'launched_projects' => (int) $allProjects->filter(fn ($p) => (bool) $p->trading_enabled || (string) $p->status === 'launched')->count(),
            'total_sales_quote' => (float) $allProjects->sum(fn ($p) => (float) $p->sold_quote),
            'total_tokens_sold' => (float) $allProjects->sum(fn ($p) => (float) $p->sold_tokens),
            'unique_holders' => $tokenSymbols
                ? (int) TradingAccount::where('account_type', 'spot')
                    ->whereIn('currency', $tokenSymbols)
                    ->where('balance', '>', 0)
                    ->distinct('user_id')
                    ->count('user_id')
                : 0,
            'total_holdings' => $tokenSymbols
                ? (float) TradingAccount::where('account_type', 'spot')
                    ->whereIn('currency', $tokenSymbols)
                    ->sum('balance')
                : 0.0,
        ];

        $projects = $query->orderBy($sort, $dir)->paginate(30)->withQueryString();

        return view("templates.{$template}.blades.admin.launchpad.index", compact('page_title', 'projects', 'stats'));
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

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:launchpad_projects,id',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->id);
        $project->update([
            'approval_status' => 'approved',
            'is_visible' => true,
            'admin_approved_at' => now(),
            'admin_approved_by' => auth()->guard('admin')->id(),
        ]);

        return back()->with('success', __('Project approved'));
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:launchpad_projects,id',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->id);
        $project->update([
            'approval_status' => 'rejected',
            'is_visible' => false,
            'admin_approved_at' => null,
            'admin_approved_by' => null,
        ]);

        return back()->with('success', __('Project rejected'));
    }

    public function updateLaunchFee(Request $request)
    {
        $request->validate([
            'fee_amount' => 'required|numeric|min:0',
            'fee_currency' => 'required|string|max:16',
        ]);

        updateSetting('launchpad_launch_fee_amount', (float) $request->fee_amount);
        updateSetting('launchpad_launch_fee_currency', strtoupper((string) $request->fee_currency));

        return back()->with('success', __('Launch fee updated'));
    }
}
