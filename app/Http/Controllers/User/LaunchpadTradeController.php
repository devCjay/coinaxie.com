<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadMarket;
use App\Models\LaunchpadOrder;
use App\Models\LaunchpadProject;
use App\Models\LaunchpadTrade;
use App\Services\LaunchpadMatchingService;
use App\Services\LaunchpadWalletService;
use Illuminate\Http\Request;

class LaunchpadTradeController extends Controller
{
    public function index(string $slug, LaunchpadWalletService $wallet)
    {
        $page_title = __('Launchpad Trading');
        $template = config('site.template');

        $project = LaunchpadProject::where('slug', $slug)->firstOrFail();
        if (!$project->trading_enabled) {
            abort(404);
        }

        $market = LaunchpadMarket::where('project_id', $project->id)->firstOrFail();
        if ($market->status !== 'active') {
            abort(404);
        }

        $baseAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $market->base_currency);
        $quoteAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $market->quote_currency);

        $orderBook = $this->orderBook($market);
        $recentTrades = LaunchpadTrade::where('market_id', $market->id)->latest()->take(40)->get();

        $openOrders = LaunchpadOrder::where('market_id', $market->id)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'partially_filled'])
            ->latest()
            ->get();

        $closedOrders = LaunchpadOrder::where('market_id', $market->id)
            ->where('user_id', auth()->id())
            ->whereNotIn('status', ['pending', 'partially_filled'])
            ->latest()
            ->take(50)
            ->get();

        return view("templates.{$template}.blades.user.launchpad.trade", compact(
            'page_title',
            'project',
            'market',
            'baseAccount',
            'quoteAccount',
            'orderBook',
            'recentTrades',
            'openOrders',
            'closedOrders'
        ));
    }

    public function placeOrder(Request $request, LaunchpadMatchingService $matching)
    {
        $request->validate([
            'market_id' => 'required|integer|exists:launchpad_markets,id',
            'side' => 'required|in:buy,sell',
            'type' => 'required|in:limit,market',
            'price' => 'nullable|numeric|min:0',
            'quote_amount' => 'nullable|numeric|min:0',
            'base_amount' => 'nullable|numeric|min:0',
        ]);

        $market = LaunchpadMarket::findOrFail((int) $request->market_id);

        try {
            $order = $matching->placeOrder(auth()->user(), $market, [
                'side' => $request->side,
                'type' => $request->type,
                'price' => $request->price,
                'quote_amount' => $request->quote_amount,
                'base_amount' => $request->base_amount,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Order placed'),
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Order Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }

    public function cancelOrder(Request $request, LaunchpadMatchingService $matching)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:launchpad_orders,id',
        ]);

        $order = LaunchpadOrder::with('market')->findOrFail((int) $request->order_id);

        try {
            $canceled = $matching->cancelOrder(auth()->user(), $order);
            return response()->json([
                'status' => 'success',
                'message' => __('Order canceled'),
                'order' => $canceled,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cancel Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }

    protected function orderBook(LaunchpadMarket $market): array
    {
        $asks = LaunchpadOrder::selectRaw('price, SUM(base_qty - filled_base_qty) as qty')
            ->where('market_id', $market->id)
            ->where('side', 'sell')
            ->whereIn('status', ['pending', 'partially_filled'])
            ->whereNotNull('price')
            ->groupBy('price')
            ->orderBy('price', 'asc')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [(float) $r->price, (float) $r->qty])
            ->values()
            ->all();

        $bids = LaunchpadOrder::selectRaw('price, SUM(base_qty - filled_base_qty) as qty')
            ->where('market_id', $market->id)
            ->where('side', 'buy')
            ->whereIn('status', ['pending', 'partially_filled'])
            ->whereNotNull('price')
            ->groupBy('price')
            ->orderBy('price', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [(float) $r->price, (float) $r->qty])
            ->values()
            ->all();

        return [
            'asks' => $asks,
            'bids' => $bids,
        ];
    }
}

