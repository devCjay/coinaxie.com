<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomMarketToken;
use Illuminate\Http\Request;

class CustomTokenController extends Controller
{
    public function index(Request $request)
    {
        $page_title = __('Custom Tokens');
        $template = config('site.template');

        $search = trim((string) $request->get('search', ''));
        $market = (string) $request->get('market', 'all');
        $market = in_array($market, ['futures', 'margin', 'both', 'all'], true) ? $market : 'all';

        $query = CustomMarketToken::query()
            ->when($search !== '', function ($q) use ($search) {
                $term = strtoupper($search);
                return $q->where('ticker', 'like', "%{$term}%");
            })
            ->when($market !== 'all', function ($q) use ($market) {
                return $q->where('market', $market);
            })
            ->orderBy('ticker', 'asc');

        $statsQuery = CustomMarketToken::query();
        $stats = [
            'total' => (int) $statsQuery->count(),
            'active' => (int) (clone $statsQuery)->where('is_active', true)->count(),
            'futures' => (int) (clone $statsQuery)->whereIn('market', ['futures', 'both'])->count(),
            'margin' => (int) (clone $statsQuery)->whereIn('market', ['margin', 'both'])->count(),
            'both' => (int) (clone $statsQuery)->where('market', 'both')->count(),
        ];

        $tokens = $query->paginate(20)->withQueryString();

        return view("templates.{$template}.blades.admin.custom-tokens.index", compact('page_title', 'tokens', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'market' => 'required|in:futures,margin,both',
            'ticker' => 'required|string|max:32',
            'current_price' => 'required|numeric|min:0',
            'change_1d_percentage' => 'nullable|numeric',
            'open_price' => 'nullable|numeric|min:0',
            'high' => 'nullable|numeric|min:0',
            'low' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $ticker = strtoupper(trim((string) $request->ticker));

        CustomMarketToken::query()->updateOrCreate(
            ['ticker' => $ticker],
            [
                'market' => (string) $request->market,
                'current_price' => (float) $request->current_price,
                'change_1d_percentage' => (float) ($request->change_1d_percentage ?? 0),
                'open_price' => $request->open_price !== null ? (float) $request->open_price : null,
                'high' => $request->high !== null ? (float) $request->high : null,
                'low' => $request->low !== null ? (float) $request->low : null,
                'volume' => $request->volume !== null ? (float) $request->volume : null,
                'is_active' => (bool) ($request->boolean('is_active', true)),
            ]
        );

        return back()->with('success', __('Token saved'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:custom_market_tokens,id',
            'market' => 'required|in:futures,margin,both',
            'ticker' => 'required|string|max:32',
            'current_price' => 'required|numeric|min:0',
            'change_1d_percentage' => 'nullable|numeric',
            'open_price' => 'nullable|numeric|min:0',
            'high' => 'nullable|numeric|min:0',
            'low' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $token = CustomMarketToken::findOrFail((int) $request->id);
        $ticker = strtoupper(trim((string) $request->ticker));

        $token->update([
            'market' => (string) $request->market,
            'ticker' => $ticker,
            'current_price' => (float) $request->current_price,
            'change_1d_percentage' => (float) ($request->change_1d_percentage ?? 0),
            'open_price' => $request->open_price !== null ? (float) $request->open_price : null,
            'high' => $request->high !== null ? (float) $request->high : null,
            'low' => $request->low !== null ? (float) $request->low : null,
            'volume' => $request->volume !== null ? (float) $request->volume : null,
            'is_active' => (bool) ($request->boolean('is_active', true)),
        ]);

        return back()->with('success', __('Token updated'));
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:custom_market_tokens,id',
        ]);

        CustomMarketToken::query()->whereKey((int) $request->id)->delete();

        return back()->with('success', __('Token deleted'));
    }
}

