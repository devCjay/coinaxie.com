@extends('templates.bento.blades.layouts.user')

@section('content')
    @php
        $symbol = strtoupper($market->base_currency) . '/' . strtoupper($market->quote_currency);
        $lastPrice = (float) ($market->last_price ?? 0);
        $baseBal = (float) ($baseAccount->balance ?? 0);
        $quoteBal = (float) ($quoteAccount->balance ?? 0);
    @endphp

    <div class="min-h-screen px-2 md:px-0">
        <div class="mb-4 md:mb-6 bg-secondary border border-white/5 rounded-2xl p-5">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-white text-lg md:text-xl font-semibold">{{ $page_title }}</h2>
                    <p class="text-white/55 text-sm">{{ $project->name }} • {{ $symbol }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('user.launchpad.show', $project->slug) }}"
                        class="bg-white/5 border border-white/10 text-white/80 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-white/10 transition">
                        {{ __('Project') }}
                    </a>
                    <a href="{{ route('user.launchpad.index') }}"
                        class="bg-white/5 border border-white/10 text-white/80 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-white/10 transition">
                        {{ __('All') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5">
            <div class="bg-secondary border border-white/5 rounded-2xl p-5">
                <h3 class="text-white font-semibold">{{ __('Place Order') }}</h3>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Balance') }} {{ strtoupper($market->base_currency) }}</div>
                        <div class="text-white font-semibold mt-1">
                            {{ rtrim(rtrim(number_format($baseBal, 8, '.', ''), '0'), '.') }}
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <div class="text-white/55 text-xs">{{ __('Balance') }} {{ strtoupper($market->quote_currency) }}</div>
                        <div class="text-white font-semibold mt-1">
                            {{ rtrim(rtrim(number_format($quoteBal, 8, '.', ''), '0'), '.') }}
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Side') }}</label>
                    <select class="order-side bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full">
                        <option value="buy">{{ __('Buy') }}</option>
                        <option value="sell">{{ __('Sell') }}</option>
                    </select>
                </div>

                <div class="mt-3">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Type') }}</label>
                    <select class="order-type bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full">
                        <option value="limit">{{ __('Limit') }}</option>
                        <option value="market">{{ __('Market') }}</option>
                    </select>
                </div>

                <div class="mt-3 price-wrap">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Price') }} ({{ strtoupper($market->quote_currency) }})</label>
                    <input type="number" step="0.00000001" min="0"
                        class="order-price bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full"
                        value="{{ $lastPrice > 0 ? rtrim(rtrim(number_format($lastPrice, 8, '.', ''), '0'), '.') : '' }}"
                        placeholder="0.00">
                </div>

                <div class="mt-3 buy-amount-wrap">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Amount') }} ({{ strtoupper($market->quote_currency) }})</label>
                    <input type="number" step="0.00000001" min="0"
                        class="order-quote bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full"
                        placeholder="0.00">
                    <div class="text-xs text-white/45 mt-2">
                        {{ __('Estimated') }}:
                        <span class="buy-est">0</span> {{ strtoupper($market->base_currency) }}
                    </div>
                </div>

                <div class="mt-3 sell-amount-wrap hidden">
                    <label class="text-xs text-white/55 block mb-1">{{ __('Amount') }} ({{ strtoupper($market->base_currency) }})</label>
                    <input type="number" step="0.00000001" min="0"
                        class="order-base bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80 w-full"
                        placeholder="0.00">
                </div>

                <div class="mt-5">
                    <button
                        class="btn-place w-full bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-3 text-sm font-semibold hover:bg-accent-primary/25 transition"
                        data-market-id="{{ $market->id }}">
                        {{ __('Place Order') }}
                    </button>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-semibold">{{ __('Order Book') }}</h3>
                    <div class="text-white/55 text-sm">
                        {{ __('Last') }}:
                        <span class="text-white font-semibold">{{ rtrim(rtrim(number_format($lastPrice, 8, '.', ''), '0'), '.') }}</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-white/55 mb-2">{{ __('Asks') }}</div>
                        <div class="space-y-2 max-h-[360px] overflow-y-auto">
                            @forelse ($orderBook['asks'] as $row)
                                <div class="flex items-center justify-between text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-2">
                                    <div class="text-red-300">{{ rtrim(rtrim(number_format((float) $row[0], 8, '.', ''), '0'), '.') }}</div>
                                    <div class="text-white/80">{{ rtrim(rtrim(number_format((float) $row[1], 8, '.', ''), '0'), '.') }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-white/45">{{ __('No asks') }}</div>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-white/55 mb-2">{{ __('Bids') }}</div>
                        <div class="space-y-2 max-h-[360px] overflow-y-auto">
                            @forelse ($orderBook['bids'] as $row)
                                <div class="flex items-center justify-between text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-2">
                                    <div class="text-green-300">{{ rtrim(rtrim(number_format((float) $row[0], 8, '.', ''), '0'), '.') }}</div>
                                    <div class="text-white/80">{{ rtrim(rtrim(number_format((float) $row[1], 8, '.', ''), '0'), '.') }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-white/45">{{ __('No bids') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-2xl p-5">
                <h3 class="text-white font-semibold">{{ __('Recent Trades') }}</h3>
                <div class="mt-4 space-y-2 max-h-[430px] overflow-y-auto">
                    @forelse ($recentTrades as $t)
                        <div class="flex items-center justify-between text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-2">
                            <div class="{{ $t->taker_side === 'buy' ? 'text-green-300' : 'text-red-300' }}">
                                {{ ucfirst($t->taker_side) }}
                            </div>
                            <div class="text-white/80">
                                {{ rtrim(rtrim(number_format((float) $t->price, 8, '.', ''), '0'), '.') }}
                            </div>
                            <div class="text-white/60">
                                {{ rtrim(rtrim(number_format((float) $t->base_qty, 8, '.', ''), '0'), '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-white/45">{{ __('No trades yet') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-4 md:mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">
            <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
                <div class="p-5">
                    <h3 class="text-white font-semibold">{{ __('Open Orders') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-dark/50 text-text-secondary">
                            <tr>
                                <th class="text-left px-5 py-3">{{ __('Side') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Type') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Price') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Amount') }}</th>
                                <th class="text-right px-5 py-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($openOrders as $o)
                                <tr class="border-t border-white/5">
                                    <td class="px-5 py-4">
                                        <span class="{{ $o->side === 'buy' ? 'text-green-300' : 'text-red-300' }}">{{ ucfirst($o->side) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-white/80">{{ strtoupper($o->type) }}</td>
                                    <td class="px-5 py-4 text-white/80">
                                        {{ $o->price ? rtrim(rtrim(number_format((float) $o->price, 8, '.', ''), '0'), '.') : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-white/80">
                                        {{ rtrim(rtrim(number_format((float) $o->base_qty, 8, '.', ''), '0'), '.') }}
                                        <span class="text-white/45">({{ rtrim(rtrim(number_format((float) $o->filled_base_qty, 8, '.', ''), '0'), '.') }} {{ __('filled') }})</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <button
                                            class="btn-cancel bg-red-500/15 border border-red-500/25 text-red-300 rounded-xl px-4 py-2 font-semibold hover:bg-red-500/20 transition"
                                            data-order-id="{{ $o->id }}">
                                            {{ __('Cancel') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-text-secondary">
                                        {{ __('No open orders') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
                <div class="p-5">
                    <h3 class="text-white font-semibold">{{ __('Order History') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-dark/50 text-text-secondary">
                            <tr>
                                <th class="text-left px-5 py-3">{{ __('Side') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Type') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Price') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Amount') }}</th>
                                <th class="text-left px-5 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($closedOrders as $o)
                                <tr class="border-t border-white/5">
                                    <td class="px-5 py-4">
                                        <span class="{{ $o->side === 'buy' ? 'text-green-300' : 'text-red-300' }}">{{ ucfirst($o->side) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-white/80">{{ strtoupper($o->type) }}</td>
                                    <td class="px-5 py-4 text-white/80">
                                        {{ $o->price ? rtrim(rtrim(number_format((float) $o->price, 8, '.', ''), '0'), '.') : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-white/80">
                                        {{ rtrim(rtrim(number_format((float) $o->base_qty, 8, '.', ''), '0'), '.') }}
                                        <span class="text-white/45">({{ rtrim(rtrim(number_format((float) $o->filled_base_qty, 8, '.', ''), '0'), '.') }} {{ __('filled') }})</span>
                                    </td>
                                    <td class="px-5 py-4 text-white/80">{{ ucfirst(str_replace('_', ' ', $o->status)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-text-secondary">
                                        {{ __('No order history') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            function fmt8(v) {
                const n = Number(v || 0);
                return n.toFixed(8).replace(/\.?0+$/, '');
            }

            function syncForm() {
                const side = $('.order-side').val();
                const type = $('.order-type').val();

                if (type === 'market') {
                    $('.price-wrap').addClass('hidden');
                } else {
                    $('.price-wrap').removeClass('hidden');
                }

                if (side === 'buy') {
                    $('.buy-amount-wrap').removeClass('hidden');
                    $('.sell-amount-wrap').addClass('hidden');
                } else {
                    $('.buy-amount-wrap').addClass('hidden');
                    $('.sell-amount-wrap').removeClass('hidden');
                }

                updateEstimate();
            }

            function updateEstimate() {
                const side = $('.order-side').val();
                if (side !== 'buy') {
                    return;
                }
                const type = $('.order-type').val();
                const q = parseFloat($('.order-quote').val() || 0);
                const p = parseFloat($('.order-price').val() || 0);
                const est = (type === 'limit' && p > 0) ? (q / p) : 0;
                $('.buy-est').text(fmt8(est));
            }

            $(document).on('change', '.order-side, .order-type', syncForm);
            $(document).on('input', '.order-quote, .order-price', updateEstimate);
            syncForm();

            $(document).on('click', '.btn-place', function() {
                const marketId = $(this).data('market-id');
                const side = $('.order-side').val();
                const type = $('.order-type').val();
                const price = $('.order-price').val();
                const quoteAmount = $('.order-quote').val();
                const baseAmount = $('.order-base').val();
                const $btn = $(this);

                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('user.launchpad.trade.order') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        market_id: marketId,
                        side: side,
                        type: type,
                        price: type === 'limit' ? price : null,
                        quote_amount: side === 'buy' ? quoteAmount : null,
                        base_amount: side === 'sell' ? baseAmount : null,
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            toastNotification(res.message, 'success');
                            window.location.reload();
                        } else {
                            toastNotification(res.message || "{{ __('Failed') }}", 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON ? xhr.responseJSON.message : "{{ __('An error occurred') }}";
                        toastNotification(message, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.btn-cancel', function() {
                const orderId = $(this).data('order-id');
                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('user.launchpad.trade.cancel') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        order_id: orderId,
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            toastNotification(res.message, 'success');
                            window.location.reload();
                        } else {
                            toastNotification(res.message || "{{ __('Failed') }}", 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON ? xhr.responseJSON.message : "{{ __('An error occurred') }}";
                        toastNotification(message, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
