<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadPurchase;
use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use App\Services\LaunchpadWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaunchpadController extends Controller
{
    public function index(LaunchpadWalletService $wallet)
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $projectsQuery = LaunchpadProject::query()
            ->where('approval_status', 'approved')
            ->where('is_visible', true)
            ->whereIn('status', ['live', 'ended', 'launched']);

        $projects = (clone $projectsQuery)->latest()->get();

        $projectIds = $projects->pluck('id')->all();
        $investorsByProject = [];
        $totalInvestors = 0;
        if (!empty($projectIds)) {
            $investorsByProject = LaunchpadPurchase::selectRaw('project_id, COUNT(DISTINCT user_id) as investors')
                ->whereIn('project_id', $projectIds)
                ->groupBy('project_id')
                ->pluck('investors', 'project_id')
                ->toArray();

            $totalInvestors = (int) LaunchpadPurchase::whereIn('project_id', $projectIds)
                ->distinct('user_id')
                ->count('user_id');
        }

        $avgFunded = 0.0;
        $avgFundedCount = 0;
        foreach ($projects as $p) {
            $hardCap = (float) $p->hard_cap_quote;
            if ($hardCap > 0) {
                $avgFunded += min(100.0, ((float) $p->sold_quote / $hardCap) * 100.0);
                $avgFundedCount++;
            }
        }
        $avgFunded = $avgFundedCount > 0 ? ($avgFunded / $avgFundedCount) : 0.0;

        $stats = [
            'projects' => (int) $projects->count(),
            'investors' => $totalInvestors,
            'total_raised' => (float) $projects->sum(fn ($p) => (float) $p->sold_quote),
            'avg_funded' => $avgFunded,
        ];

        $feeAmount = (float) getSetting('launchpad_launch_fee_amount', 0);
        $feeCurrency = strtoupper((string) getSetting('launchpad_launch_fee_currency', 'USDT'));
        $feeAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $feeCurrency);

        $myProjects = LaunchpadProject::where('created_by_user_id', auth()->id())
            ->latest()
            ->take(20)
            ->get();

        return view("templates.{$template}.blades.user.launchpad.index", compact(
            'page_title',
            'projects',
            'investorsByProject',
            'stats',
            'feeAmount',
            'feeCurrency',
            'feeAccount',
            'myProjects'
        ));
    }

    public function show(string $slug, LaunchpadWalletService $wallet)
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $project = LaunchpadProject::where('slug', $slug)->firstOrFail();
        if ((!$project->is_visible || $project->approval_status !== 'approved') && (int) $project->created_by_user_id !== (int) auth()->id()) {
            abort(404);
        }

        $myReserved = $project->purchases()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['reserved', 'allocated'])
            ->sum('quote_amount');

        $quoteAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $project->quote_currency);
        $tokenAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $project->token_symbol);

        return view("templates.{$template}.blades.user.launchpad.show", compact(
            'page_title',
            'project',
            'myReserved',
            'quoteAccount',
            'tokenAccount'
        ));
    }

    public function buy(Request $request, LaunchpadService $launchpad)
    {
        $request->validate([
            'project_id' => 'required|integer|exists:launchpad_projects,id',
            'quote_amount' => 'required|numeric|min:0.00000001',
        ]);

        $project = LaunchpadProject::findOrFail((int) $request->project_id);
        if (!$project->is_visible || $project->approval_status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => __('Order Failed') . ' - ' . __('Project not available'),
            ], 422);
        }

        try {
            $purchase = $launchpad->buy(auth()->user(), $project, (float) $request->quote_amount);
            return response()->json([
                'status' => 'success',
                'message' => __('Purchase successful'),
                'purchase' => $purchase,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Order Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }

    public function submit(Request $request, LaunchpadWalletService $wallet, LaunchpadService $launchpad)
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

        $feeAmount = (float) getSetting('launchpad_launch_fee_amount', 0);
        $feeCurrency = strtoupper((string) getSetting('launchpad_launch_fee_currency', 'USDT'));

        try {
            if ($feeAmount > 0) {
                $wallet->debitSpot(auth()->user(), $feeCurrency, $feeAmount);
            }

            $slug = Str::slug($request->name . '-' . strtoupper($request->token_symbol));
            if (LaunchpadProject::where('slug', $slug)->exists()) {
                $slug .= '-' . Str::random(6);
            }

            $project = LaunchpadProject::create([
                'created_by_user_id' => auth()->id(),
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
                'approval_status' => 'pending',
                'is_visible' => false,
                'trading_enabled' => false,
                'launch_fee_currency' => $feeCurrency,
                'launch_fee_amount' => $feeAmount,
                'launch_fee_paid_at' => $feeAmount > 0 ? now() : null,
            ]);

            $launchpad->ensureMarket($project);

            return response()->json([
                'status' => 'success',
                'message' => __('Project submitted. Waiting for admin approval.'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Submission Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }
}
