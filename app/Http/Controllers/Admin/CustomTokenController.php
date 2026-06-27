<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomMarketToken;
use App\Services\LozandServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if ($request->ajax()) {
            return view("templates.{$template}.blades.admin.custom-tokens.inner", compact('tokens'));
        }

        return view("templates.{$template}.blades.admin.custom-tokens.index", compact('page_title', 'tokens', 'stats'));
    }

    public function importFromApi(Request $request)
    {
        $request->validate([
            'overwrite' => 'nullable|boolean',
        ]);

        $overwrite = (bool) $request->boolean('overwrite', false);

        $lozand = new LozandServices();
        $futures = $lozand->futureTickersFromApi();
        if (($futures['status'] ?? null) !== 'success') {
            return back()->with('error', __('Failed to fetch Futures tickers from API'));
        }
        $margins = $lozand->marginsFromApi();
        if (($margins['status'] ?? null) !== 'success') {
            return back()->with('error', __('Failed to fetch Margin tickers from API'));
        }

        $map = [];
        $merge = function (array $item, string $market) use (&$map) {
            $ticker = strtoupper(trim((string) ($item['ticker'] ?? '')));
            if ($ticker === '') {
                return;
            }

            $current = (float) ($item['current_price'] ?? 0);
            if ($current <= 0) {
                return;
            }

            $open = (float) ($item['open_price'] ?? 0);
            $high = (float) ($item['high'] ?? 0);
            $low = (float) ($item['low'] ?? 0);
            $volume = (float) ($item['volume'] ?? 0);
            $change = (float) ($item['change_1d_percentage'] ?? 0);

            if (!isset($map[$ticker])) {
                $map[$ticker] = [
                    'ticker' => $ticker,
                    'market' => $market,
                    'current_price' => $current,
                    'open_price' => $open > 0 ? $open : null,
                    'high' => $high > 0 ? $high : null,
                    'low' => $low > 0 ? $low : null,
                    'volume' => $volume > 0 ? $volume : null,
                    'change_1d_percentage' => $change,
                    'is_active' => 1,
                ];
                return;
            }

            $existingMarket = (string) ($map[$ticker]['market'] ?? $market);
            if ($existingMarket !== $market) {
                $map[$ticker]['market'] = 'both';
            }
        };

        $fList = $futures['data'] ?? [];
        if (is_array($fList)) {
            foreach ($fList as $item) {
                if (is_array($item)) {
                    $merge($item, 'futures');
                }
            }
        }

        $mList = $margins['data'] ?? [];
        if (is_array($mList)) {
            foreach ($mList as $item) {
                if (is_array($item)) {
                    $merge($item, 'margin');
                }
            }
        }

        $now = now();
        $rows = [];
        foreach ($map as $row) {
            $rows[] = array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!$rows) {
            return back()->with('error', __('No tickers returned from API'));
        }

        $updateCols = $overwrite
            ? ['market', 'current_price', 'open_price', 'high', 'low', 'volume', 'change_1d_percentage', 'is_active', 'updated_at']
            : ['market', 'updated_at'];

        DB::table('custom_market_tokens')->upsert($rows, ['ticker'], $updateCols);

        return back()->with('success', __('Imported tokens from API: ') . number_format(count($rows)));
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
