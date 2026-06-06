<?php

namespace App\Services;

use App\Models\CustomMarketToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LozandServices
{
    /**
     * This is a custom unified API for market data, based on live market data 
     * aggregated by our custom API service.
     * 
     * @var string
     */
    public $base_url;
    public $api_key;
    public $binance_spot_base_url;
    public $binance_futures_base_url;
    public $bybit_base_url;
    public $okx_base_url;
    public $market_data_providers;

    public function __construct()
    {
        $this->base_url = config('services.binso.base_url');
        $this->api_key = safeDecrypt(config('services.binso.api_key'));
        $this->binance_spot_base_url = config('services.binance.spot_base_url') ?? 'https://api.binance.com';
        $this->binance_futures_base_url = config('services.binance.futures_base_url') ?? 'https://fapi.binance.com';
        $this->bybit_base_url = config('services.bybit.base_url') ?? 'https://api.bybit.com';
        $this->okx_base_url = config('services.okx.base_url') ?? 'https://www.okx.com';
        $this->market_data_providers = config('services.market_data.providers') ?? ['binance', 'bybit', 'okx'];
        if (is_string($this->market_data_providers)) {
            $this->market_data_providers = array_values(array_filter(array_map('trim', explode(',', $this->market_data_providers))));
        }
        if (!is_array($this->market_data_providers) || empty($this->market_data_providers)) {
            $this->market_data_providers = ['binance', 'bybit', 'okx'];
        }
    }

    protected function withMarketDataFallback(callable $fn): array
    {
        $last = null;
        foreach ($this->market_data_providers as $provider) {
            $provider = strtolower(trim((string) $provider));
            if ($provider === '') {
                continue;
            }
            $result = $fn($provider);
            if (is_array($result) && ($result['status'] ?? null) === 'success') {
                return $result;
            }
            $last = $result;
        }
        return is_array($last) ? $last : [
            'status' => 'error',
            'message' => 'No market data providers configured',
            'code' => 500
        ];
    }

    protected function parseSymbolBaseQuote(string $symbol): array
    {
        $quotes = ['USDT', 'USDC', 'BUSD', 'FDUSD', 'BTC', 'ETH', 'BNB', 'EUR', 'TRY'];
        foreach ($quotes as $quote) {
            if (str_ends_with($symbol, $quote) && strlen($symbol) > strlen($quote)) {
                return [substr($symbol, 0, -strlen($quote)), $quote];
            }
        }
        return [$symbol, ''];
    }

    protected function marketPriceSource(): string
    {
        return ((string) getSetting('trading_market_price_source', 'api')) === 'custom' ? 'custom' : 'api';
    }

    protected function customMarketPricesForMarket(string $market): array
    {
        if (Schema::hasTable('custom_market_tokens')) {
            $market = in_array($market, ['futures', 'margin'], true) ? $market : 'futures';
            $rows = CustomMarketToken::query()
                ->where('is_active', true)
                ->where(function ($q) use ($market) {
                    $q->where('market', 'both')->orWhere('market', $market);
                })
                ->orderBy('ticker', 'asc')
                ->get([
                    'ticker',
                    'market',
                    'current_price',
                    'open_price',
                    'high',
                    'low',
                    'volume',
                    'change_1d_percentage',
                ]);

            $out = [];
            foreach ($rows as $row) {
                $ticker = strtoupper(trim((string) $row->ticker));
                $price = (float) ($row->current_price ?? 0);
                if ($ticker === '' || $price <= 0) {
                    continue;
                }
                $open = (float) ($row->open_price ?? 0);
                $high = (float) ($row->high ?? 0);
                $low = (float) ($row->low ?? 0);
                $volume = (float) ($row->volume ?? 0);
                $change = (float) ($row->change_1d_percentage ?? 0);

                if ($open <= 0) {
                    $open = $price;
                }
                if ($high <= 0) {
                    $high = $price;
                }
                if ($low <= 0) {
                    $low = $price;
                }

                [$base, $quote] = $this->parseSymbolBaseQuote($ticker);

                $out[] = [
                    'ticker' => $ticker,
                    'base' => $base,
                    'quote' => $quote,
                    'current_price' => $price,
                    'open_price' => $open,
                    'high' => $high,
                    'low' => $low,
                    'volume' => $volume,
                    'change_1d_percentage' => $change,
                ];
            }
            return $out;
        }

        $raw = getSetting('trading_custom_market_prices', '[]');
        $items = is_array($raw) ? $raw : json_decode((string) $raw, true);
        $items = is_array($items) ? $items : [];
        $market = in_array($market, ['futures', 'margin'], true) ? $market : 'futures';

        $out = [];
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowMarket = (string) ($row['market'] ?? 'both');
            if (!in_array($rowMarket, ['futures', 'margin', 'both'], true)) {
                $rowMarket = 'both';
            }
            if ($rowMarket !== 'both' && $rowMarket !== $market) {
                continue;
            }

            $ticker = strtoupper(trim((string) ($row['ticker'] ?? '')));
            $price = (float) ($row['current_price'] ?? 0);
            if ($ticker === '' || $price <= 0) {
                continue;
            }

            $open = (float) ($row['open_price'] ?? 0);
            $high = (float) ($row['high'] ?? 0);
            $low = (float) ($row['low'] ?? 0);
            $volume = (float) ($row['volume'] ?? 0);
            $change = (float) ($row['change_1d_percentage'] ?? 0);

            if ($open <= 0) {
                $open = $price;
            }
            if ($high <= 0) {
                $high = $price;
            }
            if ($low <= 0) {
                $low = $price;
            }

            [$base, $quote] = $this->parseSymbolBaseQuote($ticker);

            $out[] = [
                'ticker' => $ticker,
                'base' => $base,
                'quote' => $quote,
                'current_price' => $price,
                'open_price' => $open,
                'high' => $high,
                'low' => $low,
                'volume' => $volume,
                'change_1d_percentage' => $change,
            ];
        }

        usort($out, function ($a, $b) {
            return strcmp((string) ($a['ticker'] ?? ''), (string) ($b['ticker'] ?? ''));
        });

        return $out;
    }

    protected function customMarketTicker(string $market, string $ticker): ?array
    {
        $ticker = strtoupper(trim($ticker));
        if ($ticker === '') {
            return null;
        }
        if (Schema::hasTable('custom_market_tokens')) {
            $market = in_array($market, ['futures', 'margin'], true) ? $market : 'futures';
            $row = CustomMarketToken::query()
                ->where('is_active', true)
                ->where('ticker', $ticker)
                ->where(function ($q) use ($market) {
                    $q->where('market', 'both')->orWhere('market', $market);
                })
                ->first();

            if (!$row) {
                return null;
            }

            $price = (float) ($row->current_price ?? 0);
            if ($price <= 0) {
                return null;
            }

            $open = (float) ($row->open_price ?? 0);
            $high = (float) ($row->high ?? 0);
            $low = (float) ($row->low ?? 0);
            $volume = (float) ($row->volume ?? 0);
            $change = (float) ($row->change_1d_percentage ?? 0);

            if ($open <= 0) {
                $open = $price;
            }
            if ($high <= 0) {
                $high = $price;
            }
            if ($low <= 0) {
                $low = $price;
            }

            [$base, $quote] = $this->parseSymbolBaseQuote($ticker);

            return [
                'ticker' => $ticker,
                'base' => $base,
                'quote' => $quote,
                'current_price' => $price,
                'open_price' => $open,
                'high' => $high,
                'low' => $low,
                'volume' => $volume,
                'change_1d_percentage' => $change,
            ];
        }
        $list = $this->customMarketPricesForMarket($market);
        foreach ($list as $row) {
            if (isset($row['ticker']) && (string) $row['ticker'] === $ticker) {
                return $row;
            }
        }
        return null;
    }

    protected function syntheticOrderBookForPrice(float $price): array
    {
        $price = $price > 0 ? $price : 1;
        $step = $price > 1000 ? 1 : ($price > 100 ? 0.1 : ($price > 1 ? 0.01 : 0.0001));
        $levels = 50;

        $asks = [];
        $bids = [];
        for ($i = 1; $i <= $levels; $i++) {
            $askPrice = $price + ($i * $step);
            $bidPrice = max($step, $price - ($i * $step));
            $qtyBase = 0.01 + (($i % 9) * 0.01);
            $asks[] = [round($askPrice, 8), round($qtyBase, 8)];
            $bids[] = [round($bidPrice, 8), round($qtyBase, 8)];
        }

        return ['asks' => $asks, 'bids' => $bids];
    }

    protected function syntheticRecentTradesForPrice(float $price): array
    {
        $price = $price > 0 ? $price : 1;
        $step = $price > 1000 ? 1 : ($price > 100 ? 0.1 : ($price > 1 ? 0.01 : 0.0001));
        $nowMs = (int) now()->valueOf();
        $out = [];
        for ($i = 0; $i < 50; $i++) {
            $direction = ($i % 2) === 0 ? 1 : -1;
            $jitter = (($i % 5) + 1) * $step * 0.4;
            $tradePrice = max($step, $price + ($direction * $jitter));
            $qty = 0.01 + (($i % 10) * 0.01);
            $out[] = [
                'price' => (float) round($tradePrice, 8),
                'qty' => (float) round($qty, 8),
                'time' => $nowMs - ($i * 2000),
                'isBuyerMaker' => ($i % 2) === 0,
            ];
        }
        return $out;
    }

    protected function binanceErrorMessage($response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            if (isset($json['msg']) && is_string($json['msg'])) {
                return $json['msg'];
            }
            if (isset($json['message']) && is_string($json['message'])) {
                return $json['message'];
            }
        }
        return 'Request failed with status: ' . $response->status();
    }

    protected function normalizeBinance24hTicker(array $item): array
    {
        $symbol = (string) ($item['symbol'] ?? '');
        [$base, $quote] = $this->parseSymbolBaseQuote($symbol);

        return [
            'ticker' => $symbol,
            'base' => $base,
            'quote' => $quote,
            'current_price' => (float) ($item['lastPrice'] ?? 0),
            'open_price' => (float) ($item['openPrice'] ?? 0),
            'high' => (float) ($item['highPrice'] ?? 0),
            'low' => (float) ($item['lowPrice'] ?? 0),
            'volume' => (float) ($item['volume'] ?? 0),
            'change_1d_percentage' => (float) ($item['priceChangePercent'] ?? 0),
        ];
    }

    protected function bybitErrorMessage($response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            if (isset($json['retMsg']) && is_string($json['retMsg']) && $json['retMsg'] !== '') {
                return $json['retMsg'];
            }
            if (isset($json['msg']) && is_string($json['msg']) && $json['msg'] !== '') {
                return $json['msg'];
            }
        }
        return 'Request failed with status: ' . $response->status();
    }

    protected function normalizeBybitTicker(array $item): array
    {
        $symbol = (string) ($item['symbol'] ?? '');
        [$base, $quote] = $this->parseSymbolBaseQuote($symbol);
        $pct = (float) ($item['price24hPcnt'] ?? 0);
        if (abs($pct) <= 1) {
            $pct *= 100;
        }

        return [
            'ticker' => $symbol,
            'base' => $base,
            'quote' => $quote,
            'current_price' => (float) ($item['lastPrice'] ?? 0),
            'open_price' => (float) ($item['prevPrice24h'] ?? 0),
            'high' => (float) ($item['highPrice24h'] ?? 0),
            'low' => (float) ($item['lowPrice24h'] ?? 0),
            'volume' => (float) ($item['volume24h'] ?? 0),
            'change_1d_percentage' => $pct,
        ];
    }

    protected function okxErrorMessage($response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            if (isset($json['msg']) && is_string($json['msg']) && $json['msg'] !== '') {
                return $json['msg'];
            }
        }
        return 'Request failed with status: ' . $response->status();
    }

    protected function okxSpotInstIdFromSymbol(string $symbol): string
    {
        [$base, $quote] = $this->parseSymbolBaseQuote($symbol);
        if ($quote === '') {
            return $symbol;
        }
        return $base . '-' . $quote;
    }

    protected function okxFuturesInstIdFromSymbol(string $symbol): string
    {
        [$base, $quote] = $this->parseSymbolBaseQuote($symbol);
        if ($quote === '') {
            return $symbol;
        }
        return $base . '-' . $quote . '-SWAP';
    }

    protected function okxSymbolFromInstId(string $instId): string
    {
        $parts = explode('-', (string) $instId);
        if (count($parts) >= 2) {
            return $parts[0] . $parts[1];
        }
        return str_replace('-', '', (string) $instId);
    }

    protected function normalizeOkxTicker(array $item): array
    {
        $instId = (string) ($item['instId'] ?? '');
        $symbol = $this->okxSymbolFromInstId($instId);
        [$base, $quote] = $this->parseSymbolBaseQuote($symbol);
        $last = (float) ($item['last'] ?? 0);
        $open = (float) ($item['open24h'] ?? 0);
        $pct = 0.0;
        if ($open > 0) {
            $pct = (($last - $open) / $open) * 100;
        }

        return [
            'ticker' => $symbol,
            'base' => $base,
            'quote' => $quote,
            'current_price' => $last,
            'open_price' => $open,
            'high' => (float) ($item['high24h'] ?? 0),
            'low' => (float) ($item['low24h'] ?? 0),
            'volume' => (float) ($item['vol24h'] ?? 0),
            'change_1d_percentage' => $pct,
        ];
    }

    protected function normalizeTradesToBinanceShape(array $trades, string $sideKey, string $priceKey, string $qtyKey, string $timeKey): array
    {
        $out = [];
        foreach ($trades as $trade) {
            if (!is_array($trade)) {
                continue;
            }
            $side = strtolower((string) ($trade[$sideKey] ?? $trade['side'] ?? ''));
            $price = $trade[$priceKey] ?? $trade['price'] ?? $trade['px'] ?? 0;
            $qty = $trade[$qtyKey] ?? $trade['qty'] ?? $trade['size'] ?? $trade['sz'] ?? 0;
            $time = $trade[$timeKey] ?? $trade['time'] ?? $trade['execTime'] ?? $trade['ts'] ?? 0;
            $out[] = [
                'isBuyerMaker' => $side === 'buy',
                'price' => (float) $price,
                'qty' => (float) $qty,
                'time' => (int) $time,
            ];
        }
        return $out;
    }

    /**
     * Get market stocks data.
     *
     * @return array
     */
    public function marketStocks()
    {
        if (Cache::has('market_stocks')) {
            return Cache::get('market_stocks');
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'x-api-key' => $this->api_key,
            ])->get($this->base_url . '/stocks');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put('market_stocks', $data, now()->addMinutes(2));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get ticker information.
     *
     * @param string $ticker
     * @return array
     */
    public function ticker($ticker)
    {
        $cacheKey = 'ticker_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/stocks/' . $ticker);

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }



    /**
     * Get market ETFs data.
     *
     * @return array
     */
    public function marketEtfs()
    {
        if (Cache::has('market_etfs')) {
            return Cache::get('market_etfs');
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/etfs');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put('market_etfs', $data, now()->addMinutes(2));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }



    /**
     * Get ETF ticker information.
     *
     * @param string $ticker
     * @return array
     */
    public function etfTicker($ticker)
    {
        $cacheKey = 'ticker_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/etfs/' . $ticker);

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get bonds data.
     *
     * @return array
     */
    public function bonds()
    {
        if (Cache::has('bonds')) {
            return Cache::get('bonds');
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/bonds');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put('bonds', $data, now()->addMinutes(2));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get a single bond by "cusip".
     *
     * @param string $cusip
     * @return array
     */
    public function bond($cusip)
    {
        $cacheKey = 'bond_' . $cusip;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/bonds/' . $cusip);

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Fetch future tickers data.
     *
     * @return array
     */
    public function futureTickers()
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            return [
                'status' => 'success',
                'data' => $this->customMarketPricesForMarket('futures'),
                'code' => 200,
            ];
        }

        $cacheKey = 'future_tickers_' . $source;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_futures_base_url . '/fapi/v1/ticker/24hr');
                        if ($response->successful()) {
                            $items = $response->json();
                            $normalized = [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    if (is_array($item)) {
                                        $normalized[] = $this->normalizeBinance24hTicker($item);
                                    }
                                }
                            }
                            return [
                                'status' => 'success',
                                'data' => $normalized,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'linear',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeBybitTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/tickers', [
                            'instType' => 'SWAP',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeOkxTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public function futureTickersFromApi(): array
    {
        $cacheKey = 'future_tickers_api_import';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_futures_base_url . '/fapi/v1/ticker/24hr');
                        if ($response->successful()) {
                            $items = $response->json();
                            $normalized = [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    if (is_array($item)) {
                                        $normalized[] = $this->normalizeBinance24hTicker($item);
                                    }
                                }
                            }
                            return [
                                'status' => 'success',
                                'data' => $normalized,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'linear',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeBybitTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/tickers', [
                            'instType' => 'SWAP',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeOkxTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(10));
            }

            return $data;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get single future ticker data.
     *
     * @param string $ticker
     * @return array
     */
    public function futureTicker($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('futures', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $row,
                'code' => 200,
            ];
        }

        $cacheKey = 'future_ticker_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_futures_base_url . '/fapi/v1/ticker/24hr', [
                            'symbol' => $ticker,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }
                            return [
                                'status' => 'success',
                                'data' => $this->normalizeBinance24hTicker($json),
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'linear',
                            'symbol' => $ticker,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeBybitTicker($first),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxFuturesInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/ticker', [
                            'instId' => $instId,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeOkxTicker($first),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get mutual funds data.
     *
     * @return array
     */
    public function mutualFunds()
    {
        $cacheKey = 'mutual_funds';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/mutual-funds');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get futures order book data.
     *
     * @param string $ticker
     * @return array
     */
    public function futuresOrderBook($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('futures', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $this->syntheticOrderBookForPrice((float) ($row['current_price'] ?? 0)),
                'code' => 200,
            ];
        }

        $cacheKey = 'futures_order_book_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_futures_base_url . '/fapi/v1/depth', [
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }

                            $asks = [];
                            $bids = [];

                            if (isset($json['asks']) && is_array($json['asks'])) {
                                foreach ($json['asks'] as $ask) {
                                    if (is_array($ask) && isset($ask[0], $ask[1])) {
                                        $asks[] = [(float) $ask[0], (float) $ask[1]];
                                    }
                                }
                            }

                            if (isset($json['bids']) && is_array($json['bids'])) {
                                foreach ($json['bids'] as $bid) {
                                    if (is_array($bid) && isset($bid[0], $bid[1])) {
                                        $bids[] = [(float) $bid[0], (float) $bid[1]];
                                    }
                                }
                            }

                            return [
                                'status' => 'success',
                                'data' => [
                                    'asks' => $asks,
                                    'bids' => $bids,
                                ],
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/orderbook', [
                            'category' => 'linear',
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $result = $json['result'] ?? [];
                                $asksRaw = is_array($result) ? ($result['a'] ?? []) : [];
                                $bidsRaw = is_array($result) ? ($result['b'] ?? []) : [];

                                $asks = [];
                                $bids = [];

                                if (is_array($asksRaw)) {
                                    foreach ($asksRaw as $ask) {
                                        if (is_array($ask) && isset($ask[0], $ask[1])) {
                                            $asks[] = [(float) $ask[0], (float) $ask[1]];
                                        }
                                    }
                                }
                                if (is_array($bidsRaw)) {
                                    foreach ($bidsRaw as $bid) {
                                        if (is_array($bid) && isset($bid[0], $bid[1])) {
                                            $bids[] = [(float) $bid[0], (float) $bid[1]];
                                        }
                                    }
                                }

                                return [
                                    'status' => 'success',
                                    'data' => [
                                        'asks' => $asks,
                                        'bids' => $bids,
                                    ],
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxFuturesInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/books', [
                            'instId' => $instId,
                            'sz' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    $asksRaw = $first['asks'] ?? [];
                                    $bidsRaw = $first['bids'] ?? [];
                                    $asks = [];
                                    $bids = [];
                                    if (is_array($asksRaw)) {
                                        foreach ($asksRaw as $ask) {
                                            if (is_array($ask) && isset($ask[0], $ask[1])) {
                                                $asks[] = [(float) $ask[0], (float) $ask[1]];
                                            }
                                        }
                                    }
                                    if (is_array($bidsRaw)) {
                                        foreach ($bidsRaw as $bid) {
                                            if (is_array($bid) && isset($bid[0], $bid[1])) {
                                                $bids[] = [(float) $bid[0], (float) $bid[1]];
                                            }
                                        }
                                    }
                                    return [
                                        'status' => 'success',
                                        'data' => [
                                            'asks' => $asks,
                                            'bids' => $bids,
                                        ],
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get futures recent trades data.
     *
     * @param string $ticker
     * @return array
     */
    public function futuresRecentTrades($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('futures', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $this->syntheticRecentTradesForPrice((float) ($row['current_price'] ?? 0)),
                'code' => 200,
            ];
        }

        $cacheKey = 'futures_recent_trades_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_futures_base_url . '/fapi/v1/trades', [
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }
                            return [
                                'status' => 'success',
                                'data' => $json,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/recent-trade', [
                            'category' => 'linear',
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                if (is_array($list)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeTradesToBinanceShape($list, 'side', 'price', 'size', 'time'),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxFuturesInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/trades', [
                            'instId' => $instId,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                if (is_array($list)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeTradesToBinanceShape($list, 'side', 'px', 'sz', 'ts'),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get margins data.
     *
     * @return array
     */
    public function margins()
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            return [
                'status' => 'success',
                'data' => $this->customMarketPricesForMarket('margin'),
                'code' => 200,
            ];
        }

        $cacheKey = 'margins_' . $source;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_spot_base_url . '/api/v3/ticker/24hr');
                        if ($response->successful()) {
                            $items = $response->json();
                            $normalized = [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    if (is_array($item)) {
                                        $normalized[] = $this->normalizeBinance24hTicker($item);
                                    }
                                }
                            }
                            return [
                                'status' => 'success',
                                'data' => $normalized,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'spot',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeBybitTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/tickers', [
                            'instType' => 'SPOT',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeOkxTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addMinutes(5));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public function marginsFromApi(): array
    {
        $cacheKey = 'margins_api_import';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_spot_base_url . '/api/v3/ticker/24hr');
                        if ($response->successful()) {
                            $items = $response->json();
                            $normalized = [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    if (is_array($item)) {
                                        $normalized[] = $this->normalizeBinance24hTicker($item);
                                    }
                                }
                            }
                            return [
                                'status' => 'success',
                                'data' => $normalized,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'spot',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeBybitTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/tickers', [
                            'instType' => 'SPOT',
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $normalized = [];
                                if (is_array($list)) {
                                    foreach ($list as $item) {
                                        if (is_array($item)) {
                                            $normalized[] = $this->normalizeOkxTicker($item);
                                        }
                                    }
                                }
                                return [
                                    'status' => 'success',
                                    'data' => $normalized,
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(10));
            }

            return $data;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get margin data.
     *
     * @param string $ticker
     * @return array
     */
    public function margin($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('margin', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $row,
                'code' => 200,
            ];
        }

        $cacheKey = 'margin_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_spot_base_url . '/api/v3/ticker/24hr', [
                            'symbol' => $ticker,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }
                            return [
                                'status' => 'success',
                                'data' => $this->normalizeBinance24hTicker($json),
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/tickers', [
                            'category' => 'spot',
                            'symbol' => $ticker,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeBybitTicker($first),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxSpotInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/ticker', [
                            'instId' => $instId,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeOkxTicker($first),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addMinutes(5));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get margin order book data.
     *
     * @param string $ticker
     * @return array
     */
    public function marginOrderBook($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('margin', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $this->syntheticOrderBookForPrice((float) ($row['current_price'] ?? 0)),
                'code' => 200,
            ];
        }

        $cacheKey = 'margin_order_book_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_spot_base_url . '/api/v3/depth', [
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }

                            $asks = [];
                            $bids = [];

                            if (isset($json['asks']) && is_array($json['asks'])) {
                                foreach ($json['asks'] as $ask) {
                                    if (is_array($ask) && isset($ask[0], $ask[1])) {
                                        $asks[] = [(float) $ask[0], (float) $ask[1]];
                                    }
                                }
                            }

                            if (isset($json['bids']) && is_array($json['bids'])) {
                                foreach ($json['bids'] as $bid) {
                                    if (is_array($bid) && isset($bid[0], $bid[1])) {
                                        $bids[] = [(float) $bid[0], (float) $bid[1]];
                                    }
                                }
                            }

                            return [
                                'status' => 'success',
                                'data' => [
                                    'asks' => $asks,
                                    'bids' => $bids,
                                ],
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/orderbook', [
                            'category' => 'spot',
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $result = $json['result'] ?? [];
                                $asksRaw = is_array($result) ? ($result['a'] ?? []) : [];
                                $bidsRaw = is_array($result) ? ($result['b'] ?? []) : [];

                                $asks = [];
                                $bids = [];

                                if (is_array($asksRaw)) {
                                    foreach ($asksRaw as $ask) {
                                        if (is_array($ask) && isset($ask[0], $ask[1])) {
                                            $asks[] = [(float) $ask[0], (float) $ask[1]];
                                        }
                                    }
                                }
                                if (is_array($bidsRaw)) {
                                    foreach ($bidsRaw as $bid) {
                                        if (is_array($bid) && isset($bid[0], $bid[1])) {
                                            $bids[] = [(float) $bid[0], (float) $bid[1]];
                                        }
                                    }
                                }

                                return [
                                    'status' => 'success',
                                    'data' => [
                                        'asks' => $asks,
                                        'bids' => $bids,
                                    ],
                                    'code' => $response->status()
                                ];
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxSpotInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/books', [
                            'instId' => $instId,
                            'sz' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                $first = is_array($list) ? ($list[0] ?? null) : null;
                                if (is_array($first)) {
                                    $asksRaw = $first['asks'] ?? [];
                                    $bidsRaw = $first['bids'] ?? [];
                                    $asks = [];
                                    $bids = [];
                                    if (is_array($asksRaw)) {
                                        foreach ($asksRaw as $ask) {
                                            if (is_array($ask) && isset($ask[0], $ask[1])) {
                                                $asks[] = [(float) $ask[0], (float) $ask[1]];
                                            }
                                        }
                                    }
                                    if (is_array($bidsRaw)) {
                                        foreach ($bidsRaw as $bid) {
                                            if (is_array($bid) && isset($bid[0], $bid[1])) {
                                                $bids[] = [(float) $bid[0], (float) $bid[1]];
                                            }
                                        }
                                    }
                                    return [
                                        'status' => 'success',
                                        'data' => [
                                            'asks' => $asks,
                                            'bids' => $bids,
                                        ],
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get margin recent trades data.
     *
     * @param string $ticker
     * @return array
     */
    public function marginRecentTrades($ticker)
    {
        $source = $this->marketPriceSource();
        if ($source === 'custom') {
            $row = $this->customMarketTicker('margin', (string) $ticker);
            if (!$row) {
                return [
                    'status' => 'error',
                    'message' => 'Ticker not found in custom market prices',
                    'code' => 404,
                ];
            }
            return [
                'status' => 'success',
                'data' => $this->syntheticRecentTradesForPrice((float) ($row['current_price'] ?? 0)),
                'code' => 200,
            ];
        }

        $cacheKey = 'margin_recent_trades_' . $source . '_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $data = $this->withMarketDataFallback(function ($provider) use ($ticker) {
                try {
                    if ($provider === 'binance') {
                        $response = Http::timeout(30)->get($this->binance_spot_base_url . '/api/v3/trades', [
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (!is_array($json)) {
                                return [
                                    'status' => 'error',
                                    'message' => 'Unexpected response format',
                                    'code' => 500
                                ];
                            }
                            return [
                                'status' => 'success',
                                'data' => $json,
                                'code' => $response->status()
                            ];
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->binanceErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'bybit') {
                        $response = Http::timeout(30)->get($this->bybit_base_url . '/v5/market/recent-trade', [
                            'category' => 'spot',
                            'symbol' => $ticker,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            $retCode = is_array($json) ? (int) ($json['retCode'] ?? 1) : 1;
                            if ($retCode === 0) {
                                $list = $json['result']['list'] ?? [];
                                if (is_array($list)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeTradesToBinanceShape($list, 'side', 'price', 'size', 'time'),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->bybitErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    if ($provider === 'okx') {
                        $instId = $this->okxSpotInstIdFromSymbol($ticker);
                        $response = Http::timeout(30)->get($this->okx_base_url . '/api/v5/market/trades', [
                            'instId' => $instId,
                            'limit' => 50,
                        ]);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && (string) ($json['code'] ?? '') === '0') {
                                $list = $json['data'] ?? [];
                                if (is_array($list)) {
                                    return [
                                        'status' => 'success',
                                        'data' => $this->normalizeTradesToBinanceShape($list, 'side', 'px', 'sz', 'ts'),
                                        'code' => $response->status()
                                    ];
                                }
                            }
                        }
                        Log::error($response->body());
                        return [
                            'status' => 'error',
                            'message' => $this->okxErrorMessage($response),
                            'code' => $response->status()
                        ];
                    }

                    return [
                        'status' => 'error',
                        'message' => 'Unsupported market data provider: ' . $provider,
                        'code' => 500
                    ];
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    return [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => 500
                    ];
                }
            });

            if (($data['status'] ?? null) === 'success') {
                Cache::put($cacheKey, $data, now()->addSeconds(6));
            }
            return $data;

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }


    /**
     * Get Forex tickers
     * @return array
     */
    public function forexTickers()
    {
        $cacheKey = 'forex_tickers';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/forex/tickers');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Get ticker
     * @param string $ticker
     * @return array
     */
    public function forexTicker($ticker)
    {
        $ticker = str_replace('/', '_', $ticker);
        $cacheKey = 'forex_ticker_' . $ticker;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/forex/tickers/' . $ticker);

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                Cache::put($cacheKey, $data, now()->addMinutes(5));
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }



    /**
     * Get IP
     * @return array
     */
    public function getIp()
    {

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->api_key,
                ])
                ->get($this->base_url . '/ip');

            if ($response->successful()) {
                $data = [
                    'status' => 'success',
                    'data' => $response->json()['data'],
                    'code' => $response->status()
                ];
                return $data;
            }

            $error_message = $response->json()['message']
                ?? 'Request failed with status: ' . $response->status();

            Log::error($response->body());

            return [
                'status' => 'error',
                'message' => $error_message,
                'code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500
            ];
        }
    }



}
