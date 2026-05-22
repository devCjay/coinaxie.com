<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LaunchpadPurchase;
use App\Models\LaunchpadPaymentIntent;
use App\Models\LaunchpadProject;
use App\Services\LaunchpadService;
use App\Services\LaunchpadWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LaunchpadController extends Controller
{
    protected function resolveWeb3Config(): array
    {
        $activeChain = strtolower((string) getSetting('launchpad_web3_active_chain', 'bsc'));
        if (!in_array($activeChain, ['bsc', 'eth', 'custom'], true)) {
            $activeChain = 'bsc';
        }

        $cfg = [
            'enabled' => false,
            'active_chain' => $activeChain,
            'chain_name' => '',
            'chain_id' => 0,
            'rpc_url' => '',
            'receiver_address' => '',
            'token_address' => '',
            'token_decimals' => 6,
            'token_symbol' => 'USDT',
        ];

        if ($activeChain === 'bsc') {
            $cfg['chain_name'] = 'BSC';
            $cfg['chain_id'] = (int) getSetting('launchpad_web3_bsc_chain_id', 56);
            $cfg['rpc_url'] = (string) getSetting('launchpad_web3_bsc_rpc_url', '');
            $cfg['receiver_address'] = (string) getSetting('launchpad_web3_bsc_receiver_address', '');
            $cfg['token_address'] = (string) getSetting('launchpad_web3_bsc_token_address', '');
            $cfg['token_decimals'] = (int) getSetting('launchpad_web3_bsc_token_decimals', 18);
            $cfg['token_symbol'] = (string) getSetting('launchpad_web3_bsc_token_symbol', 'USDT');
        } elseif ($activeChain === 'eth') {
            $cfg['chain_name'] = 'Ethereum';
            $cfg['chain_id'] = (int) getSetting('launchpad_web3_eth_chain_id', 1);
            $cfg['rpc_url'] = (string) getSetting('launchpad_web3_eth_rpc_url', '');
            $cfg['receiver_address'] = (string) getSetting('launchpad_web3_eth_receiver_address', '');
            $cfg['token_address'] = (string) getSetting('launchpad_web3_eth_token_address', '');
            $cfg['token_decimals'] = (int) getSetting('launchpad_web3_eth_token_decimals', 6);
            $cfg['token_symbol'] = (string) getSetting('launchpad_web3_eth_token_symbol', 'USDT');
        } else {
            $cfg['chain_name'] = (string) getSetting('launchpad_web3_custom_chain_name', '');
            $cfg['chain_id'] = (int) getSetting('launchpad_web3_custom_chain_id', 0);
            $cfg['rpc_url'] = (string) getSetting('launchpad_web3_custom_rpc_url', '');
            $cfg['receiver_address'] = (string) getSetting('launchpad_web3_custom_receiver_address', '');
            $cfg['token_address'] = (string) getSetting('launchpad_web3_custom_token_address', '');
            $cfg['token_decimals'] = (int) getSetting('launchpad_web3_custom_token_decimals', 6);
            $cfg['token_symbol'] = (string) getSetting('launchpad_web3_custom_token_symbol', 'USDT');
        }

        $cfg['rpc_url'] = trim((string) $cfg['rpc_url']);
        $cfg['receiver_address'] = trim((string) $cfg['receiver_address']);
        $cfg['token_address'] = trim((string) $cfg['token_address']);
        $cfg['token_symbol'] = strtoupper(trim((string) $cfg['token_symbol']));

        $cfg['enabled'] = (int) $cfg['chain_id'] > 0
            && $cfg['rpc_url'] !== ''
            && $cfg['receiver_address'] !== ''
            && $cfg['token_address'] !== '';

        if (!$cfg['enabled']) {
            $legacy = [
                'chain_id' => (int) getSetting('launchpad_web3_chain_id', 0),
                'rpc_url' => (string) getSetting('launchpad_web3_rpc_url', ''),
                'receiver_address' => (string) getSetting('launchpad_web3_receiver_address', ''),
                'token_address' => (string) getSetting('launchpad_web3_token_address', ''),
                'token_decimals' => (int) getSetting('launchpad_web3_token_decimals', 6),
                'token_symbol' => (string) getSetting('launchpad_web3_token_symbol', 'USDT'),
            ];
            $legacy['rpc_url'] = trim((string) $legacy['rpc_url']);
            $legacy['receiver_address'] = trim((string) $legacy['receiver_address']);
            $legacy['token_address'] = trim((string) $legacy['token_address']);
            $legacy['token_symbol'] = strtoupper(trim((string) $legacy['token_symbol']));
            $legacyEnabled = $legacy['chain_id'] > 0 && $legacy['rpc_url'] !== '' && $legacy['receiver_address'] !== '' && $legacy['token_address'] !== '';

            if ($legacyEnabled) {
                $cfg = array_merge($cfg, $legacy);
                $cfg['active_chain'] = $cfg['active_chain'] ?: 'bsc';
                $cfg['enabled'] = true;
            }
        }

        return $cfg;
    }

    public function index(LaunchpadWalletService $wallet)
    {
        $page_title = __('Launchpad');
        $template = config('site.template');

        $projectsQuery = LaunchpadProject::query()
            ->where('approval_status', 'approved')
            ->where('is_visible', true)
            ->where('status', '!=', 'canceled');

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

        $tokenAccount = $wallet->getOrCreateSpotAccount(auth()->user(), $project->token_symbol);
        $web3Config = $this->resolveWeb3Config();

        return view("templates.{$template}.blades.user.launchpad.show", compact(
            'page_title',
            'project',
            'myReserved',
            'tokenAccount',
            'web3Config'
        ));
    }

    public function buy(Request $request, LaunchpadService $launchpad)
    {
        return response()->json([
            'status' => 'error',
            'message' => __('Please use WalletConnect to complete this purchase'),
        ], 422);
    }

    public function web3Intent(Request $request)
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

        $quoteAmount = (string) $request->quote_amount;

        try {
            $now = now();
            if ((string) $project->status !== 'live') {
                throw new \RuntimeException('Sale not live');
            }
            if ($project->sale_start_at && $now->lt($project->sale_start_at)) {
                throw new \RuntimeException('Sale not started');
            }
            if ($project->sale_end_at && $now->gt($project->sale_end_at)) {
                throw new \RuntimeException('Sale ended');
            }

            $min = (float) $project->min_buy_quote;
            if ($min > 0 && $quoteAmount < $min) {
                throw new \RuntimeException('Below minimum');
            }

            $max = (float) $project->max_buy_quote;
            if ($max > 0) {
                $userTotal = (float) LaunchpadPurchase::where('project_id', $project->id)
                    ->where('user_id', auth()->id())
                    ->whereIn('status', ['reserved', 'allocated'])
                    ->sum('quote_amount');
                if (($userTotal + $quoteAmount) > $max) {
                    throw new \RuntimeException('Above maximum');
                }
            }

            $hardCap = (float) $project->hard_cap_quote;
            if ($hardCap > 0 && ((float) $project->sold_quote + (float) $quoteAmount) > $hardCap) {
                throw new \RuntimeException('Hard cap reached');
            }

            $web3 = $this->resolveWeb3Config();
            if (!(bool) ($web3['enabled'] ?? false)) {
                throw new \RuntimeException('WalletConnect not configured');
            }

            $chainId = (int) ($web3['chain_id'] ?? 0);
            $rpcUrl = (string) ($web3['rpc_url'] ?? '');
            $receiver = (string) ($web3['receiver_address'] ?? '');
            $tokenAddress = (string) ($web3['token_address'] ?? '');
            $tokenDecimals = (int) ($web3['token_decimals'] ?? 6);
            $tokenSymbol = strtoupper((string) ($web3['token_symbol'] ?? 'USDT'));

            if (strtoupper((string) $project->quote_currency) !== $tokenSymbol) {
                throw new \RuntimeException('Unsupported quote currency');
            }

            $amountBaseUnits = $this->decimalToBaseUnits((string) $quoteAmount, $tokenDecimals);

            $reference = (string) Str::orderedUuid();
            $expiresAt = now()->addMinutes(20)->timestamp;

            $intent = LaunchpadPaymentIntent::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'provider' => 'web3',
                'reference' => $reference,
                'status' => 'pending',
                'quote_amount' => (float) $quoteAmount,
                'quote_currency' => strtoupper((string) $project->quote_currency),
                'pay_currency' => $tokenSymbol,
                'pay_amount' => null,
                'pay_address' => $receiver,
                'payment_id' => null,
                'payment_status' => null,
                'expires_at' => $expiresAt,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Wallet payment initialized'),
                'intent' => [
                    'reference' => $intent->reference,
                    'receiver_address' => $receiver,
                    'chain_id' => $chainId,
                    'rpc_url' => $rpcUrl,
                    'token_address' => $tokenAddress,
                    'token_decimals' => $tokenDecimals,
                    'token_symbol' => $tokenSymbol,
                    'amount_base_units' => $amountBaseUnits,
                    'expires_at' => (int) ($intent->expires_at ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Order Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }

    public function web3Confirm(Request $request, LaunchpadService $launchpad)
    {
        $request->validate([
            'reference' => 'required|string|max:64',
            'tx_hash' => 'required|string|max:191',
        ]);

        $intent = LaunchpadPaymentIntent::where('reference', (string) $request->reference)
            ->where('user_id', auth()->id())
            ->first();

        if (!$intent) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment intent not found'),
            ], 404);
        }

        if ((string) $intent->provider !== 'web3') {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid payment provider'),
            ], 422);
        }

        if ((string) $intent->status === 'completed') {
            return response()->json([
                'status' => 'success',
                'message' => __('Payment already confirmed'),
            ]);
        }

        if ($intent->expires_at && now()->timestamp > (int) $intent->expires_at) {
            $intent->update(['status' => 'failed']);
            return response()->json([
                'status' => 'error',
                'message' => __('Payment intent expired'),
            ], 422);
        }

        $txHash = strtolower(trim((string) $request->tx_hash));
        if (!preg_match('/^0x[a-f0-9]{64}$/', $txHash)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid transaction hash'),
            ], 422);
        }

        $used = LaunchpadPaymentIntent::where('transaction_hash', $txHash)
            ->where('status', 'completed')
            ->exists();
        if ($used) {
            return response()->json([
                'status' => 'error',
                'message' => __('Transaction already used'),
            ], 422);
        }

        $web3 = $this->resolveWeb3Config();
        if (!(bool) ($web3['enabled'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => __('WalletConnect not configured'),
            ], 422);
        }

        $chainId = (int) ($web3['chain_id'] ?? 0);
        $rpcUrl = (string) ($web3['rpc_url'] ?? '');
        $receiver = strtolower((string) ($web3['receiver_address'] ?? ''));
        $tokenAddress = strtolower((string) ($web3['token_address'] ?? ''));
        $tokenDecimals = (int) ($web3['token_decimals'] ?? 6);
        $tokenSymbol = strtoupper((string) ($web3['token_symbol'] ?? 'USDT'));

        if ($intent->pay_currency && strtoupper((string) $intent->pay_currency) !== $tokenSymbol) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment settings changed. Please start again.'),
            ], 422);
        }
        if ($intent->pay_address && strtolower((string) $intent->pay_address) !== $receiver) {
            return response()->json([
                'status' => 'error',
                'message' => __('Receiver address changed. Please start again.'),
            ], 422);
        }

        $project = LaunchpadProject::find((int) $intent->project_id);
        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => __('Project not found'),
            ], 404);
        }

        if (strtoupper((string) $project->quote_currency) !== $tokenSymbol) {
            return response()->json([
                'status' => 'error',
                'message' => __('Unsupported quote currency'),
            ], 422);
        }

        $expectedBaseUnits = $this->decimalToBaseUnits((string) $intent->quote_amount, $tokenDecimals);
        $expectedHex = $this->decStringToHex($expectedBaseUnits);

        $receipt = $this->rpc($rpcUrl, 'eth_getTransactionReceipt', [$txHash]);
        if (!$receipt) {
            return response()->json([
                'status' => 'pending',
                'message' => __('Waiting for confirmation'),
            ], 202);
        }

        $status = strtolower((string) ($receipt['status'] ?? ''));
        if ($status !== '0x1') {
            $intent->update([
                'status' => 'failed',
                'transaction_hash' => $txHash,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => __('Transaction failed'),
            ], 422);
        }

        $logs = $receipt['logs'] ?? [];
        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
        $receiverTopic = '0x' . str_pad(ltrim($receiver, '0x'), 64, '0', STR_PAD_LEFT);
        $tokenAddressNorm = '0x' . ltrim($tokenAddress, '0x');

        $paid = false;
        foreach ($logs as $log) {
            $addr = strtolower((string) ($log['address'] ?? ''));
            if ($addr !== $tokenAddressNorm) {
                continue;
            }
            $topics = $log['topics'] ?? [];
            if (empty($topics) || strtolower((string) ($topics[0] ?? '')) !== $transferTopic) {
                continue;
            }
            $toTopic = strtolower((string) ($topics[2] ?? ''));
            if ($toTopic !== strtolower($receiverTopic)) {
                continue;
            }
            $amountHex = strtolower((string) ($log['data'] ?? '0x0'));
            if ($this->hexGte($amountHex, $expectedHex)) {
                $paid = true;
                break;
            }
        }

        if (!$paid) {
            return response()->json([
                'status' => 'pending',
                'message' => __('Waiting for payment transfer'),
            ], 202);
        }

        $blockNumber = $receipt['blockNumber'] ?? null;
        $paidAt = now();
        if ($blockNumber) {
            $block = $this->rpc($rpcUrl, 'eth_getBlockByNumber', [$blockNumber, false]);
            if ($block && isset($block['timestamp'])) {
                $ts = hexdec((string) $block['timestamp']);
                if ($ts > 0) {
                    $paidAt = \Carbon\Carbon::createFromTimestamp($ts);
                }
            }
        }

        try {
            DB::transaction(function () use ($intent, $txHash) {
                $intent->refresh();
                if ((string) $intent->status !== 'completed') {
                    $intent->update([
                        'transaction_hash' => $txHash,
                    ]);
                }
            });

            $purchase = $launchpad->buyExternal(auth()->user(), $project, (float) $intent->quote_amount, $paidAt, (string) $intent->reference);

            $intent->update([
                'status' => 'completed',
                'transaction_hash' => $txHash,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Purchase confirmed'),
                'purchase' => $purchase,
            ]);
        } catch (\Throwable $e) {
            $intent->update([
                'status' => 'failed',
                'transaction_hash' => $txHash,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => __('Order Failed') . ' - ' . $e->getMessage(),
            ], 422);
        }
    }

    protected function rpc(string $rpcUrl, string $method, array $params): ?array
    {
        $res = Http::timeout(25)->post($rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);

        if ($res->failed()) {
            return null;
        }
        $json = $res->json();
        $result = $json['result'] ?? null;
        if (!$result) {
            return null;
        }
        return is_array($result) ? $result : null;
    }

    protected function decimalToBaseUnits(string $amount, int $decimals): string
    {
        $amount = trim($amount);
        if (!preg_match('/^\d+(\.\d+)?$/', $amount)) {
            throw new \RuntimeException('Invalid amount');
        }
        $parts = explode('.', $amount, 2);
        $intPart = ltrim($parts[0], '0');
        $intPart = $intPart === '' ? '0' : $intPart;
        $fracPart = $parts[1] ?? '';
        if (strlen($fracPart) > $decimals) {
            throw new \RuntimeException('Too many decimals');
        }
        $fracPart = str_pad($fracPart, $decimals, '0', STR_PAD_RIGHT);
        $scaled = ltrim($intPart . $fracPart, '0');
        return $scaled === '' ? '0' : $scaled;
    }

    protected function decStringToHex(string $dec): string
    {
        $dec = ltrim($dec, '0');
        if ($dec === '' || $dec === '0') {
            return '0x0';
        }

        $hex = '';
        $num = $dec;
        while ($num !== '0') {
            [$num, $rem] = $this->divDecString($num, 16);
            $hex = dechex($rem) . $hex;
        }
        $hex = ltrim($hex, '0');
        return '0x' . ($hex === '' ? '0' : $hex);
    }

    protected function divDecString(string $num, int $divisor): array
    {
        $carry = 0;
        $out = '';
        $len = strlen($num);
        for ($i = 0; $i < $len; $i++) {
            $carry = $carry * 10 + (int) $num[$i];
            $digit = intdiv($carry, $divisor);
            $carry = $carry % $divisor;
            if ($out !== '' || $digit !== 0) {
                $out .= (string) $digit;
            }
        }
        return [$out === '' ? '0' : $out, $carry];
    }

    protected function hexGte(string $a, string $b): bool
    {
        $a = strtolower(ltrim($a, '0x'));
        $b = strtolower(ltrim($b, '0x'));
        $a = ltrim($a, '0');
        $b = ltrim($b, '0');
        $a = $a === '' ? '0' : $a;
        $b = $b === '' ? '0' : $b;
        if (strlen($a) !== strlen($b)) {
            return strlen($a) > strlen($b);
        }
        return strcmp($a, $b) >= 0;
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
