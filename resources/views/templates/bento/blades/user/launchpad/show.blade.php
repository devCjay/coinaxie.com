@extends('templates.bento.blades.layouts.user')

@section('content')
    @php
        $now = now();
        $saleStarts = $project->sale_start_at ? $project->sale_start_at->format('Y-m-d H:i') : null;
        $saleEnds = $project->sale_end_at ? $project->sale_end_at->format('Y-m-d H:i') : null;
        $launchAt = $project->launch_at ? $project->launch_at->format('Y-m-d H:i') : null;
        $statusLabel = match ((string) $project->status) {
            'draft' => __('Presale'),
            'live' => __('Live'),
            'ended' => __('Ended'),
            'launched' => __('Launched'),
            'canceled' => __('Canceled'),
            default => ucfirst((string) $project->status),
        };
        $isSaleLive =
            $project->status === 'live' &&
            (!$project->sale_start_at || $now->gte($project->sale_start_at)) &&
            (!$project->sale_end_at || $now->lte($project->sale_end_at));
        $hardCap = (float) $project->hard_cap_quote;
        $sold = (float) $project->sold_quote;
        $remaining = $hardCap > 0 ? max(0, $hardCap - $sold) : null;

        $countdownLabel = null;
        $countdownTargetMs = null;
        if ($project->status === 'draft' && $project->sale_start_at && $now->lt($project->sale_start_at)) {
            $countdownLabel = __('Sale starts in');
            $countdownTargetMs = $project->sale_start_at->timestamp * 1000;
        } elseif ($isSaleLive && $project->sale_end_at && $now->lt($project->sale_end_at)) {
            $countdownLabel = __('Sale ends in');
            $countdownTargetMs = $project->sale_end_at->timestamp * 1000;
        } elseif (!$project->trading_enabled && $project->launch_at && $now->lt($project->launch_at)) {
            $countdownLabel = __('Launch in');
            $countdownTargetMs = $project->launch_at->timestamp * 1000;
        }
        $isLaunchCountdown = $countdownLabel === __('Launch in');
    @endphp

    <div class="min-h-screen px-2 md:px-0">
        <div class="mb-4 md:mb-6 bg-secondary border border-white/5 rounded-2xl p-5">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    @if ($project->token_logo_url)
                        <img src="{{ $project->token_logo_url }}" alt="{{ $project->token_symbol }}"
                            class="w-12 h-12 rounded-2xl object-cover border border-white/10">
                    @else
                        <div class="w-12 h-12 rounded-2xl bg-accent-primary/20 grid place-items-center text-accent-primary font-bold">
                            {{ strtoupper(substr($project->token_symbol, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-white text-lg md:text-xl font-semibold">{{ $project->name }}</h2>
                        <p class="text-white/55 text-sm">
                            {{ strtoupper($project->token_symbol) }}/{{ strtoupper($project->quote_currency) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('user.launchpad.index') }}"
                        class="bg-white/5 border border-white/10 text-white/80 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-white/10 transition">
                        {{ __('Back') }}
                    </a>
                    @if ($project->trading_enabled)
                        <a href="{{ route('user.launchpad.trade', $project->slug) }}"
                            class="bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2 text-sm font-semibold hover:bg-accent-primary/25 transition">
                            {{ __('Trade') }}
                        </a>
                    @endif
                </div>
            </div>

            @if ($project->description)
                <div class="mt-4 text-white/70 text-sm leading-relaxed">
                    {{ $project->description }}
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5">
            <div class="lg:col-span-2 bg-secondary border border-white/5 rounded-2xl p-5">
                <h3 class="text-white font-semibold">{{ __('Sale Details') }}</h3>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Sale Price') }}</div>
                        <div class="text-white font-semibold mt-1">
                            {{ rtrim(rtrim(number_format((float) $project->sale_price, 8, '.', ''), '0'), '.') }}
                            {{ strtoupper($project->quote_currency) }}
                        </div>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Status') }}</div>
                        <div class="text-white font-semibold mt-1">{{ $statusLabel }}</div>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Sale Window') }}</div>
                        <div class="text-white/80 mt-1">
                            @if ($saleStarts || $saleEnds)
                                <div>{{ $saleStarts ?: '—' }} → {{ $saleEnds ?: '—' }}</div>
                            @else
                                —
                            @endif
                        </div>
                        @if ($countdownLabel && $countdownTargetMs)
                            <div class="text-white/55 text-xs mt-2">
                                <span class="{{ $isLaunchCountdown ? 'text-amber-400 font-semibold' : '' }}">{{ $countdownLabel }}:</span>
                                <span class="lp-countdown {{ $isLaunchCountdown ? 'text-amber-400' : 'text-white' }} font-semibold" data-target="{{ (int) $countdownTargetMs }}">—</span>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Launch At') }}</div>
                        <div class="text-white/80 mt-1">{{ $launchAt ?: '—' }}</div>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Sold') }}</div>
                        <div class="text-white font-semibold mt-1">
                            {{ rtrim(rtrim(number_format((float) $project->sold_quote, 8, '.', ''), '0'), '.') }}
                            {{ strtoupper($project->quote_currency) }}
                        </div>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Hard Cap') }}</div>
                        <div class="text-white font-semibold mt-1">
                            @if ($hardCap > 0)
                                {{ rtrim(rtrim(number_format($hardCap, 8, '.', ''), '0'), '.') }}
                                {{ strtoupper($project->quote_currency) }}
                            @else
                                —
                            @endif
                        </div>
                        @if (!is_null($remaining))
                            <div class="text-white/55 text-xs mt-1">
                                {{ __('Remaining') }}:
                                {{ rtrim(rtrim(number_format((float) $remaining, 8, '.', ''), '0'), '.') }}
                                {{ strtoupper($project->quote_currency) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-2xl p-5">
                <h3 class="text-white font-semibold">{{ __('Buy Before Launch') }}</h3>

                <div class="mt-4 bg-white/5 border border-white/10 rounded-xl p-4">
                    <div class="text-white/55 text-xs">{{ __('Your') }} {{ strtoupper($project->token_symbol) }}</div>
                    <div class="text-white font-semibold mt-1">
                        {{ rtrim(rtrim(number_format((float) ($tokenAccount->balance ?? 0), 8, '.', ''), '0'), '.') }}
                    </div>
                </div>

                <div class="mt-4 bg-white/5 border border-white/10 rounded-xl p-4">
                    <div class="text-white/55 text-xs">{{ __('Your Reserved (Quote)') }}</div>
                    <div class="text-white font-semibold mt-1">
                        {{ rtrim(rtrim(number_format((float) $myReserved, 8, '.', ''), '0'), '.') }}
                        {{ strtoupper($project->quote_currency) }}
                    </div>
                </div>

                <div class="mt-5">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Amount') }} ({{ strtoupper($project->quote_currency) }})</label>
                    <input type="number" step="0.00000001" min="0"
                        class="buy-amount bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full"
                        placeholder="0.00" {{ $isSaleLive ? '' : 'disabled' }}>
                    <div class="text-xs text-white/45 mt-2">
                        {{ __('You receive') }}:
                        <span class="buy-preview-token">0</span>
                        {{ strtoupper($project->token_symbol) }}
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-2">
                    <button type="button"
                        class="btn-buy-web3 flex-1 bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-accent-primary/25 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ ($isSaleLive && (bool) ($web3Config['enabled'] ?? false)) ? '' : 'disabled' }} data-project-id="{{ $project->id }}"
                        data-sale-price="{{ (float) $project->sale_price }}"
                        data-quote-currency="{{ strtoupper($project->quote_currency) }}">
                        {{ $isSaleLive ? __('Buy with WalletConnect') : __('Sale Not Live') }}
                    </button>
                </div>

                @if (!(bool) ($web3Config['enabled'] ?? false))
                    <div class="mt-3 text-xs text-white/50">
                        {{ __('WalletConnect is not configured. Please contact support.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/ethers@5.7.2/dist/ethers.umd.min.js"></script>
    <script src="https://unpkg.com/@walletconnect/web3-provider@1.8.0/dist/umd/index.min.js"></script>
    <script src="https://unpkg.com/web3modal@1.9.12/dist/index.js"></script>
    <script>
        $(document).ready(function() {
            const web3Cfg = @json($web3Config ?? []);
            const salePrice = parseFloat($('.btn-buy-web3').data('sale-price') || 0);

            function startCountdowns() {
                $('.lp-countdown').each(function() {
                    const $el = $(this);
                    const target = parseInt($el.data('target') || 0, 10);
                    if (!target) {
                        return;
                    }
                    const tick = function() {
                        const now = Date.now();
                        let diff = Math.max(0, target - now);
                        const totalSeconds = Math.floor(diff / 1000);
                        const d = Math.floor(totalSeconds / 86400);
                        const h = Math.floor((totalSeconds % 86400) / 3600);
                        const m = Math.floor((totalSeconds % 3600) / 60);
                        const s = totalSeconds % 60;
                        const pad = (n) => String(n).padStart(2, '0');
                        const text = d > 0 ? `${d}d ${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(h)}:${pad(m)}:${pad(s)}`;
                        $el.text(text);
                    };
                    tick();
                    setInterval(tick, 1000);
                });
            }

            function fmt8(v) {
                const n = Number(v || 0);
                return n.toFixed(8).replace(/\.?0+$/, '');
            }

            startCountdowns();

            $(document).on('input', '.buy-amount', function() {
                const q = parseFloat($(this).val() || 0);
                const t = salePrice > 0 ? (q / salePrice) : 0;
                $('.buy-preview-token').text(fmt8(t));
            });

            async function connectAndPay(intent) {
                const chainId = parseInt(intent.chain_id || 0, 10);
                const rpcUrl = (intent.rpc_url || '').toString();
                const receiver = (intent.receiver_address || '').toString();
                const tokenAddress = (intent.token_address || '').toString();
                const amountBaseUnits = (intent.amount_base_units || '0').toString();

                const providerOptions = {
                    walletconnect: {
                        package: window.WalletConnectProvider.default,
                        options: {
                            rpc: rpcUrl && chainId ? { [chainId]: rpcUrl } : {},
                            chainId: chainId || undefined,
                        }
                    }
                };

                const web3Modal = new window.Web3Modal.default({
                    cacheProvider: false,
                    providerOptions: providerOptions
                });

                const extProvider = await web3Modal.connect();
                const ethersProvider = new ethers.providers.Web3Provider(extProvider);

                if (extProvider && extProvider.request && chainId) {
                    try {
                        const hexChainId = '0x' + chainId.toString(16);
                        await extProvider.request({
                            method: 'wallet_switchEthereumChain',
                            params: [{ chainId: hexChainId }]
                        });
                    } catch (e) {
                    }
                }

                const signer = ethersProvider.getSigner();
                const abi = ["function transfer(address to, uint256 value) returns (bool)"];
                const token = new ethers.Contract(tokenAddress, abi, signer);
                const tx = await token.transfer(receiver, ethers.BigNumber.from(amountBaseUnits));
                return tx.hash;
            }

            function pollConfirm(reference, txHash) {
                const poll = function() {
                    $.ajax({
                        url: "{{ route('user.launchpad.web3.confirm') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            reference: reference,
                            tx_hash: txHash
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                toastNotification(res.message || "{{ __('Purchase confirmed') }}", 'success');
                                window.location.reload();
                                return;
                            }
                            setTimeout(poll, 6000);
                        },
                        error: function(xhr) {
                            if (xhr.status === 202) {
                                setTimeout(poll, 6000);
                                return;
                            }
                            const message = xhr.responseJSON ? xhr.responseJSON.message : "{{ __('An error occurred') }}";
                            toastNotification(message, 'error');
                        }
                    });
                };
                poll();
            }

            $(document).on('click', '.btn-buy-web3', function() {
                const projectId = $(this).data('project-id');
                const quoteAmount = $('.buy-amount').val() || '0';

                if (!web3Cfg || !web3Cfg.enabled) {
                    toastNotification("{{ __('WalletConnect is not configured') }}", 'error');
                    return;
                }

                const q = parseFloat(quoteAmount || 0);
                if (!projectId || q <= 0) {
                    toastNotification("{{ __('Invalid amount') }}", 'error');
                    return;
                }

                $.ajax({
                    url: "{{ route('user.launchpad.web3.intent') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        project_id: projectId,
                        quote_amount: quoteAmount
                    },
                    success: async function(res) {
                        if (res.status !== 'success' || !res.intent) {
                            toastNotification(res.message || "{{ __('Failed') }}", 'error');
                            return;
                        }

                        try {
                            toastNotification("{{ __('Connecting wallet...') }}", 'success');
                            const txHash = await connectAndPay(res.intent);
                            toastNotification("{{ __('Transaction sent') }}: " + txHash, 'success');
                            pollConfirm(res.intent.reference, txHash);
                        } catch (e) {
                            toastNotification((e && e.message) ? e.message : "{{ __('Wallet payment canceled') }}", 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON ? xhr.responseJSON.message : "{{ __('An error occurred') }}";
                        toastNotification(message, 'error');
                    }
                });
            });
        });
    </script>
@endsection
