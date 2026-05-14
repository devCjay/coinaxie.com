@extends('templates.bento.blades.layouts.user')

@section('content')
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .glass-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .glow-border {
            position: relative;
        }

        .glow-border:before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 1.5rem;
            background: linear-gradient(90deg, rgba(124, 58, 237, 0.35), rgba(168, 85, 247, 0.35), rgba(59, 130, 246, 0.35));
            filter: blur(18px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }

        .glow-border>* {
            position: relative;
            z-index: 1;
        }
    </style>

    <div class="min-h-screen px-2 md:px-0">
        <div class="glow-border mb-4 md:mb-6">
            <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-5">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-white text-lg md:text-xl font-semibold">{{ $page_title }}</h2>
                        <p class="text-white/55 text-sm">{{ __('Follow professional traders and automatically copy their orders.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
            @forelse ($pros as $pro)
                @php
                    $rel = $myRelationships[$pro->id] ?? null;
                    $name = $pro->display_name ?: ($pro->user->username ?? $pro->user->first_name ?? 'Trader');
                @endphp
                <div class="glow-border">
                    <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-5 h-full flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-11 h-11 rounded-full bg-accent-primary/20 grid place-items-center text-accent-primary font-bold">
                                    {{ strtoupper(substr($name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-white font-semibold">{{ $name }}</div>
                                    <div class="text-white/55 text-xs">
                                        {{ number_format($pro->followers_count ?? 0) }} {{ __('followers') }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="text-xs px-2 py-1 rounded-full {{ $rel ? 'bg-green-500/15 border border-green-500/25 text-green-400' : 'bg-white/5 border border-white/10 text-white/55' }}">
                                {{ $rel ? __('Copying') : __('Not copying') }}
                            </div>
                        </div>

                        @if ($pro->bio)
                            <div class="mt-3 text-white/70 text-sm leading-relaxed">
                                {{ $pro->bio }}
                            </div>
                        @endif

                        <div class="mt-4 space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Market') }}</label>
                                    <select
                                        class="copy-market bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}">
                                        <option value="both" {{ ($rel->market_type ?? '') === 'both' ? 'selected' : '' }}>{{ __('Both') }}</option>
                                        <option value="futures" {{ ($rel->market_type ?? '') === 'futures' ? 'selected' : '' }}>{{ __('Futures') }}</option>
                                        <option value="margin" {{ ($rel->market_type ?? '') === 'margin' ? 'selected' : '' }}>{{ __('Margin') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Max Leverage') }}</label>
                                    <input type="number"
                                        class="copy-leverage bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}" min="1" max="100"
                                        value="{{ (int) ($rel->max_leverage ?? 50) }}" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Allocation Type') }}</label>
                                    <select
                                        class="copy-allocation-type bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}">
                                        <option value="percent"
                                            {{ ($rel->allocation_type ?? 'percent') === 'percent' ? 'selected' : '' }}>
                                            {{ __('Percent') }}</option>
                                        <option value="fixed"
                                            {{ ($rel->allocation_type ?? '') === 'fixed' ? 'selected' : '' }}>
                                            {{ __('Fixed') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Allocation Value') }}</label>
                                    <input type="number"
                                        class="copy-allocation-value bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}" min="0" step="0.0001"
                                        value="{{ (float) ($rel->allocation_value ?? 10) }}" />
                                </div>
                            </div>

                            <div>
                                <label class="text-xs text-white/55 block mb-1">{{ __('Margin Mode') }}</label>
                                <select
                                    class="copy-margin-mode bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white/80 w-full"
                                    data-pro-id="{{ $pro->id }}">
                                    <option value="normal"
                                        {{ ($rel->margin_order_mode ?? 'normal') === 'normal' ? 'selected' : '' }}>
                                        {{ __('Normal') }}</option>
                                    <option value="borrow"
                                        {{ ($rel->margin_order_mode ?? '') === 'borrow' ? 'selected' : '' }}>
                                        {{ __('Borrow') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2">
                            @if ($rel)
                                <button
                                    class="btn-unfollow flex-1 bg-red-500/15 border border-red-500/25 text-red-300 rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-red-500/20 transition"
                                    data-pro-id="{{ $pro->id }}">
                                    {{ __('Stop Copying') }}
                                </button>
                            @else
                                <button
                                    class="btn-follow flex-1 bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-accent-primary/25 transition"
                                    data-pro-id="{{ $pro->id }}">
                                    {{ __('Start Copying') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="glow-border col-span-1 md:col-span-2 lg:col-span-3">
                    <div class="glass-panel rounded-2xl md:rounded-3xl p-8 text-center text-white/50">
                        {{ __('No professional traders available yet.') }}
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            function collectSettings(proId) {
                return {
                    pro_trader_id: proId,
                    market_type: $('.copy-market[data-pro-id="' + proId + '"]').val(),
                    allocation_type: $('.copy-allocation-type[data-pro-id="' + proId + '"]').val(),
                    allocation_value: $('.copy-allocation-value[data-pro-id="' + proId + '"]').val(),
                    max_leverage: $('.copy-leverage[data-pro-id="' + proId + '"]').val(),
                    margin_order_mode: $('.copy-margin-mode[data-pro-id="' + proId + '"]').val(),
                };
            }

            $(document).on('click', '.btn-follow', function() {
                const proId = $(this).data('pro-id');
                const $btn = $(this);
                const payload = collectSettings(proId);
                payload._token = "{{ csrf_token() }}";

                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('user.trading.copy-trading.follow') }}",
                    method: 'POST',
                    data: payload,
                    success: function(res) {
                        if (res.status === 'success') {
                            toastNotification(res.message, 'success');
                            window.location.reload();
                        } else {
                            toastNotification(res.message || "{{ __('Failed') }}", 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON ? xhr.responseJSON.message :
                            "{{ __('An error occurred') }}";
                        toastNotification(message, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.btn-unfollow', function() {
                const proId = $(this).data('pro-id');
                const $btn = $(this);

                $btn.prop('disabled', true);
                $.ajax({
                    url: "{{ route('user.trading.copy-trading.unfollow') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        pro_trader_id: proId
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
                        const message = xhr.responseJSON ? xhr.responseJSON.message :
                            "{{ __('An error occurred') }}";
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

