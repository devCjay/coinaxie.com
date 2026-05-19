<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use App\Services\LaunchpadWalletService;
use Illuminate\Http\Request;

class LaunchpadController extends Controller
{
    public function index()
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $projects = LaunchpadProject::latest()->get();

        return view("templates.{$template}.blades.user.launchpad.index", compact('page_title', 'projects'));
    }

    public function show(string $slug, LaunchpadWalletService $wallet)
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $project = LaunchpadProject::where('slug', $slug)->firstOrFail();

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
}

