@extends('templates.bento.blades.layouts.user')

@section('content')
    @php
        $mode = $mode ?? 'leaders';
        $fmt2 = fn($v) => number_format((float) $v, 2);
        $fmtShort = function ($v) {
            $v = (float) $v;
            if ($v >= 1000000000) {
                return number_format($v / 1000000000, 2) . 'B';
            }
            if ($v >= 1000000) {
                return number_format($v / 1000000, 2) . 'M';
            }
            if ($v >= 1000) {
                return number_format($v / 1000, 2) . 'K';
            }
            return number_format($v, 0);
        };
    @endphp

    <style>
        .ct-hero {
            background: radial-gradient(1200px 600px at 50% 0%, rgba(124, 58, 237, 0.18), rgba(2, 6, 23, 0)),
                radial-gradient(900px 420px at 20% 30%, rgba(59, 130, 246, 0.12), rgba(2, 6, 23, 0)),
                radial-gradient(900px 420px at 80% 30%, rgba(168, 85, 247, 0.12), rgba(2, 6, 23, 0));
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 90;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: rgba(2, 6, 23, 0.75);
            backdrop-filter: blur(10px);
        }

        .modal-content {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin: 6% auto;
            padding: 28px;
            width: 92%;
            max-width: 820px;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.55);
        }
    </style>

    <div class="min-h-screen px-2 md:px-0">
        @if ($mode === 'landing')
            <div class="ct-hero bg-secondary border border-white/5 rounded-[2.5rem] overflow-hidden relative">
                <div class="px-5 md:px-10 py-10 md:py-14 text-center">
                    <div
                        class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white/70">
                        <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        {{ __('Automated Copy Trading Platform') }}
                    </div>

                    <h1 class="mt-6 text-3xl md:text-6xl font-black tracking-tight text-white">
                        {{ __('Copy The Best.') }}
                        <span class="text-accent-primary">{{ __('Trade Like A Pro.') }}</span>
                    </h1>
                    <p class="mt-4 text-white/55 text-sm md:text-base max-w-2xl mx-auto">
                        {{ __('Automatically replicate the strategies of verified expert traders. Real-time execution, transparent performance, and complete control over your investments.') }}
                    </p>

                    <div class="mt-8 flex items-center justify-center gap-4 md:gap-6 flex-wrap">
                        <a href="{{ route('user.trading.copy-trading.leaders') }}"
                            class="bg-accent-primary/25 border border-accent-primary/30 text-white rounded-2xl px-7 py-3 text-sm md:text-base font-black hover:bg-accent-primary/30 transition inline-flex items-center gap-2">
                            {{ __('Start Copying Now') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </a>
                        <button type="button" id="btnBecomeLeader"
                            class="bg-white/5 border border-white/10 text-white rounded-2xl px-7 py-3 text-sm md:text-base font-black hover:bg-white/10 transition inline-flex items-center gap-2">
                            <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                </path>
                            </svg>
                            {{ __('Become a Leader') }}
                        </button>
                    </div>

                    <div
                        class="mt-8 md:mt-10 max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 text-left">
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                            <div class="text-[10px] uppercase tracking-widest font-black text-white/50">
                                {{ __('Active Leaders') }}</div>
                            <div class="text-white font-black text-lg mt-1">{{ number_format((int) ($stats['leaders'] ?? 0)) }}
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                            <div class="text-[10px] uppercase tracking-widest font-black text-white/50">
                                {{ __('Total Followers') }}</div>
                            <div class="text-white font-black text-lg mt-1">{{ number_format((int) ($stats['followers'] ?? 0)) }}
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                            <div class="text-[10px] uppercase tracking-widest font-black text-white/50">
                                {{ __('Trading Volume') }}</div>
                            <div class="text-white font-black text-lg mt-1">{{ $fmtShort($stats['volume'] ?? 0) }}</div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                            <div class="text-[10px] uppercase tracking-widest font-black text-white/50">
                                {{ __('Top ROI') }}</div>
                            <div class="text-white font-black text-lg mt-1">+{{ $fmt2($stats['top_roi'] ?? 0) }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 md:mt-14 text-center">
                <div
                    class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white/70">
                    <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m3 0V9a2 2 0 00-2-2H8a2 2 0 00-2 2v8m16 0H4">
                        </path>
                    </svg>
                    {{ __('Simple & Powerful') }}
                </div>
                <h2 class="mt-5 text-2xl md:text-5xl font-black tracking-tight text-white">{{ __('How It Works') }}</h2>
                <p class="mt-3 text-white/55 text-sm max-w-2xl mx-auto">
                    {{ __('Start copy trading in minutes with our streamlined process') }}
                </p>

                <div class="mt-7 md:mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 text-left">
                    <div class="bg-secondary border border-white/5 rounded-3xl p-6">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/25 grid place-items-center">
                            <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14m-6 0l-4.553 2.276A1 1 0 013 15.382V8.618a1 1 0 011.447-.894L9 10m6 0v4M9 10v4m6-4H9">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-4 text-white font-black">{{ __('Choose a Leader') }}</div>
                        <div class="mt-2 text-white/55 text-sm leading-relaxed">
                            {{ __('Browse verified leaders, review their profiles, and select the strategy that matches your risk appetite.') }}
                        </div>
                    </div>
                    <div class="bg-secondary border border-white/5 rounded-3xl p-6">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/15 border border-sky-500/25 grid place-items-center">
                            <svg class="w-6 h-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m2 0a2 2 0 11-4 0 2 2 0 014 0zM7 12a2 2 0 11-4 0 2 2 0 014 0zm12 0h.01M12 6v.01M12 18v.01">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-4 text-white font-black">{{ __('Configure & Subscribe') }}</div>
                        <div class="mt-2 text-white/55 text-sm leading-relaxed">
                            {{ __('Set allocation mode, risk limits, and leverage preferences. You stay in control.') }}
                        </div>
                    </div>
                    <div class="bg-secondary border border-white/5 rounded-3xl p-6">
                        <div class="w-12 h-12 rounded-2xl bg-violet-500/15 border border-violet-500/25 grid place-items-center">
                            <svg class="w-6 h-6 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="mt-4 text-white font-black">{{ __('Earn Automatically') }}</div>
                        <div class="mt-2 text-white/55 text-sm leading-relaxed">
                            {{ __('Trades are copied in real-time. Monitor performance and adjust anytime.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 md:mt-16">
                <div class="flex items-end justify-between gap-4 flex-wrap">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest text-amber-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ __('Top Performers') }}
                        </div>
                        <h2 class="mt-4 text-2xl md:text-5xl font-black tracking-tight text-white">
                            {{ __('Best Performing Leaders') }}
                        </h2>
                        <p class="mt-3 text-white/55 text-sm max-w-2xl">
                            {{ __('Discover top-rated traders with proven track records and consistent returns') }}
                        </p>
                    </div>
                    <a href="{{ route('user.trading.copy-trading.leaders') }}"
                        class="bg-white/5 border border-white/10 text-white rounded-2xl px-6 py-3 text-sm font-black hover:bg-white/10 transition inline-flex items-center gap-2">
                        {{ __('View All Leaders') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </div>

                <div class="mt-7 md:mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    @forelse ($topLeaders as $pro)
                        @php
                            $name = $pro->display_name ?: ($pro->user->username ?? $pro->user->first_name ?? 'Trader');
                            $followers = (int) ($pro->followers_count ?? 0);
                        @endphp
                        <div class="bg-secondary border border-white/5 rounded-3xl p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-accent-primary/20 border border-accent-primary/25 grid place-items-center text-accent-primary font-black">
                                        {{ strtoupper(substr($name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-white font-black">{{ $name }}</div>
                                        <div class="text-[10px] text-white/50 font-black uppercase tracking-widest mt-0.5">
                                            {{ __('Verified Leader') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs px-2 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">
                                    {{ __('Active') }}
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-3 gap-3">
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-widest font-black text-white/50">{{ __('ROI') }}</div>
                                    <div class="mt-1 text-emerald-300 font-black">+0.0%</div>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-widest font-black text-white/50">{{ __('Win Rate') }}</div>
                                    <div class="mt-1 text-white font-black">0.0%</div>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
                                    <div class="text-[10px] uppercase tracking-widest font-black text-white/50">{{ __('Followers') }}</div>
                                    <div class="mt-1 text-white font-black">{{ number_format($followers) }}</div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div class="flex items-center justify-between text-xs text-white/55">
                                    <span class="font-bold uppercase tracking-widest">{{ __('Capacity') }}</span>
                                    <span class="text-white font-black">{{ number_format($followers) }}/100</span>
                                </div>
                                <div class="mt-2 w-full h-2 bg-white/5 rounded-full overflow-hidden">
                                    @php
                                        $capPct = min(100, ($followers / 100) * 100);
                                    @endphp
                                    <div class="h-full bg-accent-primary/60" style="width: {{ $capPct }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5 flex items-center justify-between gap-3">
                                <div class="text-xs px-2 py-1 rounded-lg bg-white/5 border border-white/10 text-white/70 font-bold">
                                    {{ __('Profit Share') }} <span class="text-white font-black ml-1">0%</span>
                                </div>
                                <a href="{{ route('user.trading.copy-trading.leaders') }}"
                                    class="text-sm font-black text-white hover:text-accent-primary transition inline-flex items-center gap-2">
                                    {{ __('View Profile') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="bg-secondary border border-white/5 rounded-3xl p-10 text-center text-white/50 md:col-span-3">
                            {{ __('No leaders available yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="leaderModal" class="modal">
                <div class="modal-content">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-white font-black text-xl">{{ __('Become a Leader') }}</div>
                            <div class="text-white/55 text-sm mt-1">
                                {{ __('Submit your profile to share your trades. Your profile stays hidden until approved.') }}
                            </div>
                        </div>
                        <button type="button" id="closeLeaderModal"
                            class="bg-white/5 border border-white/10 text-white rounded-xl px-3 py-2 text-sm font-black hover:bg-white/10 transition">
                            {{ __('Close') }}
                        </button>
                    </div>

                    @php
                        $alreadyLeader = ($myPro && $myPro->status === 'active');
                        $alreadyRequested = ($myPro && $myPro->status === 'inactive');
                    @endphp

                    @if ($alreadyLeader)
                        <div class="mt-6 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl px-5 py-4 text-emerald-200">
                            {{ __('You are already a leader.') }}
                        </div>
                    @elseif ($alreadyRequested)
                        <div class="mt-6 bg-amber-500/10 border border-amber-500/20 rounded-2xl px-5 py-4 text-amber-200">
                            {{ __('Your leader request is pending approval.') }}
                        </div>
                    @else
                        <form id="leaderRequestForm" class="mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Display Name') }}</label>
                                    <input type="text" name="display_name"
                                        class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 w-full"
                                        placeholder="{{ __('e.g. AlphaTrader') }}">
                                </div>
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Bio') }}</label>
                                    <input type="text" name="bio"
                                        class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 w-full"
                                        placeholder="{{ __('Short description of your strategy') }}">
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end gap-3">
                                <button type="button" id="cancelLeaderRequest"
                                    class="bg-white/5 border border-white/10 text-white rounded-2xl px-6 py-3 text-sm font-black hover:bg-white/10 transition">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit" id="submitLeaderRequest"
                                    class="bg-accent-primary/25 border border-accent-primary/30 text-white rounded-2xl px-6 py-3 text-sm font-black hover:bg-accent-primary/30 transition">
                                    {{ __('Submit Request') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-secondary border border-white/5 rounded-[2.5rem] overflow-hidden relative">
                <div class="px-5 md:px-8 py-6 md:py-8">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <div class="text-white font-black text-xl md:text-2xl">{{ __('All Leaders') }}</div>
                            <div class="text-white/55 text-sm mt-1">{{ __('Choose a trader and start copying in minutes.') }}</div>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <a href="{{ route('user.trading.copy-trading') }}"
                                class="bg-white/5 border border-white/10 text-white rounded-2xl px-5 py-2.5 text-sm font-black hover:bg-white/10 transition">
                                {{ __('Back') }}
                            </a>
                            <div class="relative">
                                <input id="leaderSearch" type="text"
                                    class="bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-2.5 text-sm text-white/80 w-72 max-w-full"
                                    placeholder="{{ __('Search leaders...') }}">
                                <svg class="w-4 h-4 text-white/50 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6" id="leadersGrid">
                @forelse ($pros as $pro)
                    @php
                        $rel = $myRelationships[$pro->id] ?? null;
                        $name = $pro->display_name ?: ($pro->user->username ?? $pro->user->first_name ?? 'Trader');
                        $followers = (int) ($pro->followers_count ?? 0);
                    @endphp
                    <div class="bg-secondary border border-white/5 rounded-3xl p-5 h-full leader-card"
                        data-search="{{ strtolower($name) }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-accent-primary/20 border border-accent-primary/25 grid place-items-center text-accent-primary font-black">
                                    {{ strtoupper(substr($name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-white font-black leading-tight">{{ $name }}</div>
                                    <div class="text-white/55 text-xs">{{ number_format($followers) }} {{ __('followers') }}</div>
                                </div>
                            </div>
                            <div
                                class="text-xs px-2 py-1 rounded-full {{ $rel ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300' : 'bg-white/5 border border-white/10 text-white/55' }}">
                                {{ $rel ? __('Copying') : __('Not copying') }}
                            </div>
                        </div>

                        @if ($pro->bio)
                            <div class="mt-3 text-white/65 text-sm leading-relaxed">
                                {{ $pro->bio }}
                            </div>
                        @endif

                        <div class="mt-5 space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Market') }}</label>
                                    <select
                                        class="copy-market bg-white/5 border border-white/10 rounded-2xl px-3 py-2.5 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}">
                                        <option value="both" {{ ($rel->market_type ?? '') === 'both' ? 'selected' : '' }}>{{ __('Both') }}</option>
                                        <option value="futures" {{ ($rel->market_type ?? '') === 'futures' ? 'selected' : '' }}>{{ __('Futures') }}</option>
                                        <option value="margin" {{ ($rel->market_type ?? '') === 'margin' ? 'selected' : '' }}>{{ __('Margin') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Max Leverage') }}</label>
                                    <input type="number"
                                        class="copy-leverage bg-white/5 border border-white/10 rounded-2xl px-3 py-2.5 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}" min="1" max="100"
                                        value="{{ (int) ($rel->max_leverage ?? 50) }}" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-white/55 block mb-1">{{ __('Allocation Type') }}</label>
                                    <select
                                        class="copy-allocation-type bg-white/5 border border-white/10 rounded-2xl px-3 py-2.5 text-sm text-white/80 w-full"
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
                                        class="copy-allocation-value bg-white/5 border border-white/10 rounded-2xl px-3 py-2.5 text-sm text-white/80 w-full"
                                        data-pro-id="{{ $pro->id }}" min="0" step="0.0001"
                                        value="{{ (float) ($rel->allocation_value ?? 10) }}" />
                                </div>
                            </div>

                            <div>
                                <label class="text-xs text-white/55 block mb-1">{{ __('Margin Mode') }}</label>
                                <select
                                    class="copy-margin-mode bg-white/5 border border-white/10 rounded-2xl px-3 py-2.5 text-sm text-white/80 w-full"
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
                                    class="btn-unfollow flex-1 bg-red-500/15 border border-red-500/25 text-red-300 rounded-2xl px-4 py-2.5 text-sm font-black hover:bg-red-500/20 transition"
                                    data-pro-id="{{ $pro->id }}">
                                    {{ __('Stop Copying') }}
                                </button>
                            @else
                                <button
                                    class="btn-follow flex-1 bg-accent-primary/25 border border-accent-primary/30 text-white rounded-2xl px-4 py-2.5 text-sm font-black hover:bg-accent-primary/30 transition"
                                    data-pro-id="{{ $pro->id }}">
                                    {{ __('Start Copying') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-secondary border border-white/5 rounded-3xl p-10 text-center text-white/50 md:col-span-2 lg:col-span-3">
                        {{ __('No professional traders available yet.') }}
                    </div>
                @endforelse
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            const mode = @json($mode);

            if (mode === 'landing') {
                const $modal = $('#leaderModal');

                $('#btnBecomeLeader').on('click', function() {
                    $modal.show();
                });

                $('#closeLeaderModal, #cancelLeaderRequest').on('click', function() {
                    $modal.hide();
                });

                $(window).on('click', function(e) {
                    if (e.target === $modal[0]) {
                        $modal.hide();
                    }
                });

                $('#leaderRequestForm').on('submit', function(e) {
                    e.preventDefault();
                    const $btn = $('#submitLeaderRequest');
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('user.trading.copy-trading.request-leader') }}",
                        method: 'POST',
                        data: $(this).serialize() + "&_token={{ csrf_token() }}",
                        success: function(res) {
                            if (res.status === 'success') {
                                toastNotification(res.message, 'success');
                                window.location.reload();
                            } else {
                                toastNotification(res.message || "{{ __('Failed') }}", 'error');
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON ? (xhr.responseJSON.message || "{{ __('An error occurred') }}") :
                                "{{ __('An error occurred') }}";
                            toastNotification(message, 'error');
                        },
                        complete: function() {
                            $btn.prop('disabled', false);
                        }
                    });
                });
            }

            if (mode === 'leaders') {
                $('#leaderSearch').on('input', function() {
                    const q = ($(this).val() || '').toString().trim().toLowerCase();
                    $('.leader-card').each(function() {
                        const hay = ($(this).data('search') || '').toString();
                        $(this).toggle(!q || hay.includes(q));
                    });
                });
            }

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

