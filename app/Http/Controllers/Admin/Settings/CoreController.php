<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class CoreController extends Controller
{
    // index
    public function index()
    {
        $page_title = __('Core Settings');
        $template = config('site.template');

        // Timezones & Currencies list
        $timezones = json_decode(file_get_contents(public_path('assets/json/timezones.json')), true);
        $currencies = json_decode(file_get_contents(public_path('assets/json/currencies.json')), true);


        return view("templates.$template.blades.admin.settings.core", compact('page_title', 'template', 'timezones', 'currencies'));
    }

    // update
    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:191',
            'support_email' => 'required|email|max:191',
            'support_phone' => 'nullable|string|max:191',
            'app_timezone' => 'required|string',
            'currency_name' => 'required|string|max:50',
            'currency_symbol' => 'required|string|max:10',
            'currency_position' => 'required|in:before,after',
            'decimal_places' => 'required|integer|min:0|max:8',
            'logo_square' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'logo_rectangle' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'offices' => 'nullable|array',
            'offices.*.name' => 'required_with:offices|string|max:191',
            'offices.*.address' => 'required_with:offices|string',
            'offices.*.email' => 'nullable|email|max:191',
            'offices.*.phone' => 'nullable|string|max:191',

            'trading_market_price_source' => 'nullable|in:api,custom',
            'trading_custom_market_prices' => 'nullable|array',
            'trading_custom_market_prices.*.market' => 'required_with:trading_custom_market_prices|in:futures,margin,both',
            'trading_custom_market_prices.*.ticker' => 'required_with:trading_custom_market_prices|string|max:32',
            'trading_custom_market_prices.*.current_price' => 'required_with:trading_custom_market_prices|numeric|min:0',
            'trading_custom_market_prices.*.open_price' => 'nullable|numeric|min:0',
            'trading_custom_market_prices.*.high' => 'nullable|numeric|min:0',
            'trading_custom_market_prices.*.low' => 'nullable|numeric|min:0',
            'trading_custom_market_prices.*.volume' => 'nullable|numeric|min:0',
            'trading_custom_market_prices.*.change_1d_percentage' => 'nullable|numeric',

            'launchpad_web3_active_chain' => 'nullable|in:bsc,eth,custom',
            'launchpad_web3_bsc_rpc_url' => 'nullable|string|max:2048',
            'launchpad_web3_bsc_receiver_address' => 'nullable|string|max:191',
            'launchpad_web3_bsc_token_symbol' => 'nullable|string|max:16',
            'launchpad_web3_bsc_token_decimals' => 'nullable|integer|min:0|max:36',
            'launchpad_web3_bsc_token_address' => 'nullable|string|max:191',

            'launchpad_web3_eth_rpc_url' => 'nullable|string|max:2048',
            'launchpad_web3_eth_receiver_address' => 'nullable|string|max:191',
            'launchpad_web3_eth_token_symbol' => 'nullable|string|max:16',
            'launchpad_web3_eth_token_decimals' => 'nullable|integer|min:0|max:36',
            'launchpad_web3_eth_token_address' => 'nullable|string|max:191',

            'launchpad_web3_custom_chain_name' => 'nullable|string|max:64',
            'launchpad_web3_custom_chain_id' => 'nullable|integer|min:1|max:999999',
            'launchpad_web3_custom_rpc_url' => 'nullable|string|max:2048',
            'launchpad_web3_custom_receiver_address' => 'nullable|string|max:191',
            'launchpad_web3_custom_token_symbol' => 'nullable|string|max:16',
            'launchpad_web3_custom_token_decimals' => 'nullable|integer|min:0|max:36',
            'launchpad_web3_custom_token_address' => 'nullable|string|max:191',
        ]);

        // General Site Info
        updateSetting('name', $request->site_name);
        updateSetting('email', $request->support_email);
        updateSetting('phone', $request->support_phone);
        updateSetting('app_timezone', $request->app_timezone);
        updateSetting('offices', $request->offices ?: []);

        // Env Sync for Site Name and Timezone
        updateEnv('APP_NAME', $request->site_name);
        updateEnv('APP_TIMEZONE', $request->app_timezone);

        // Financials
        updateSetting('currency', $request->currency_name);
        updateSetting('currency_symbol', $request->currency_symbol);
        updateSetting('currency_symbol_position', $request->currency_position);
        updateSetting('decimal_places', $request->decimal_places);

        updateSetting('trading_market_price_source', ($request->trading_market_price_source === 'custom') ? 'custom' : 'api');
        $customMarketPrices = $request->trading_custom_market_prices ?: [];
        $customMarketPricesClean = [];
        if (is_array($customMarketPrices)) {
            foreach ($customMarketPrices as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $ticker = strtoupper(trim((string) ($row['ticker'] ?? '')));
                $market = (string) ($row['market'] ?? 'both');
                $market = in_array($market, ['futures', 'margin', 'both'], true) ? $market : 'both';
                $price = (float) ($row['current_price'] ?? 0);
                if ($ticker === '' || $price <= 0) {
                    continue;
                }
                $open = (float) ($row['open_price'] ?? 0);
                $high = (float) ($row['high'] ?? 0);
                $low = (float) ($row['low'] ?? 0);
                $volume = (float) ($row['volume'] ?? 0);
                $change = (float) ($row['change_1d_percentage'] ?? 0);

                $customMarketPricesClean[] = [
                    'market' => $market,
                    'ticker' => $ticker,
                    'current_price' => $price,
                    'open_price' => $open > 0 ? $open : null,
                    'high' => $high > 0 ? $high : null,
                    'low' => $low > 0 ? $low : null,
                    'volume' => $volume > 0 ? $volume : null,
                    'change_1d_percentage' => $change,
                ];
            }
        }
        updateSetting('trading_custom_market_prices', $customMarketPricesClean);

        // Branding (Images)
        $path = 'assets/images/';

        if ($request->hasFile('logo_square')) {
            $logoSquare = 'logo-square.' . $request->logo_square->extension();
            $request->logo_square->move(public_path($path), $logoSquare);
            $logoSquare = $logoSquare . "?v=" . time();
            updateSetting('logo_square', $logoSquare);
        }

        if ($request->hasFile('logo_rectangle')) {
            $logoRectangle = 'logo-rectangle.' . $request->logo_rectangle->extension();
            $request->logo_rectangle->move(public_path($path), $logoRectangle);
            $logoRectangle = $logoRectangle . "?v=" . time();
            updateSetting('logo_rectangle', $logoRectangle);
        }

        if ($request->hasFile('favicon')) {
            $favicon = 'favicon.' . $request->favicon->extension();
            $request->favicon->move(public_path($path), $favicon);
            $favicon = $favicon . "?v=" . time();
            updateSetting('favicon', $favicon);
        }

        updateSetting('launchpad_web3_active_chain', $request->launchpad_web3_active_chain ?: 'bsc');

        updateSetting('launchpad_web3_bsc_chain_id', 56);
        updateSetting('launchpad_web3_bsc_rpc_url', trim((string) ($request->launchpad_web3_bsc_rpc_url ?? '')));
        updateSetting('launchpad_web3_bsc_receiver_address', trim((string) ($request->launchpad_web3_bsc_receiver_address ?? '')));
        updateSetting('launchpad_web3_bsc_token_symbol', strtoupper(trim((string) ($request->launchpad_web3_bsc_token_symbol ?? 'USDT'))));
        updateSetting('launchpad_web3_bsc_token_decimals', (int) ($request->launchpad_web3_bsc_token_decimals ?? 18));
        updateSetting('launchpad_web3_bsc_token_address', trim((string) ($request->launchpad_web3_bsc_token_address ?? '')));

        updateSetting('launchpad_web3_eth_chain_id', 1);
        updateSetting('launchpad_web3_eth_rpc_url', trim((string) ($request->launchpad_web3_eth_rpc_url ?? '')));
        updateSetting('launchpad_web3_eth_receiver_address', trim((string) ($request->launchpad_web3_eth_receiver_address ?? '')));
        updateSetting('launchpad_web3_eth_token_symbol', strtoupper(trim((string) ($request->launchpad_web3_eth_token_symbol ?? 'USDT'))));
        updateSetting('launchpad_web3_eth_token_decimals', (int) ($request->launchpad_web3_eth_token_decimals ?? 6));
        updateSetting('launchpad_web3_eth_token_address', trim((string) ($request->launchpad_web3_eth_token_address ?? '')));

        updateSetting('launchpad_web3_custom_chain_name', trim((string) ($request->launchpad_web3_custom_chain_name ?? '')));
        updateSetting('launchpad_web3_custom_chain_id', (int) ($request->launchpad_web3_custom_chain_id ?? 0));
        updateSetting('launchpad_web3_custom_rpc_url', trim((string) ($request->launchpad_web3_custom_rpc_url ?? '')));
        updateSetting('launchpad_web3_custom_receiver_address', trim((string) ($request->launchpad_web3_custom_receiver_address ?? '')));
        updateSetting('launchpad_web3_custom_token_symbol', strtoupper(trim((string) ($request->launchpad_web3_custom_token_symbol ?? 'USDT'))));
        updateSetting('launchpad_web3_custom_token_decimals', (int) ($request->launchpad_web3_custom_token_decimals ?? 6));
        updateSetting('launchpad_web3_custom_token_address', trim((string) ($request->launchpad_web3_custom_token_address ?? '')));

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Core settings updated successfully. System synchronized with .env configuration.')
            ]);
        }

        return back()->with('success', __('Core settings updated successfully. System synchronized with .env configuration.'));
    }
}
