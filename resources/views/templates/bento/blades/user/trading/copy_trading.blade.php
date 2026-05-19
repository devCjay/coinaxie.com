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

        .ct-hero--landing:before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(rgba(255, 255, 255, 0.10) 1px, transparent 1px),
                radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px),
                radial-gradient(800px 420px at 50% 40%, rgba(124, 58, 237, 0.16), rgba(2, 6, 23, 0));
            background-size: 24px 24px, 40px 40px, auto;
            background-position: 0 0, 10px 10px, 0 0;
            opacity: 0.35;
            pointer-events: none;
        }

        .ct-hero--landing:after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(900px 500px at 50% 15%, rgba(255, 255, 255, 0.08), rgba(2, 6, 23, 0));
            opacity: 0.25;
            pointer-events: none;
        }

        .ct-profile {
            background: radial-gradient(1200px 600px at 10% 0%, rgba(16, 185, 129, 0.18), rgba(2, 6, 23, 0)),
                radial-gradient(1000px 520px at 70% 30%, rgba(59, 130, 246, 0.12), rgba(2, 6, 23, 0)),
                radial-gradient(900px 520px at 30% 40%, rgba(124, 58, 237, 0.10), rgba(2, 6, 23, 0));
        }

        .ct-dotted {
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 18px 18px;
            background-position: 0 0;
        }

        .ct-profit-card {
            background: radial-gradient(900px 420px at 50% 10%, rgba(255, 255, 255, 0.06), rgba(0, 0, 0, 0)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
        }

        .ct-profit-card:before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 1.5rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.10), rgba(255, 255, 255, 0));
            opacity: 0.35;
            pointer-events: none;
        }

        .ct-profit-card:after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 1.5rem;
            background: radial-gradient(700px 260px at 50% 0%, rgba(255, 255, 255, 0.06), rgba(0, 0, 0, 0));
            opacity: 0.35;
            pointer-events: none;
        }

        .ct-profit-card>* {
            position: relative;
            z-index: 1;
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
            <div class="ct-hero ct-hero--landing bg-secondary border border-white/5 rounded-[2.5rem] overflow-hidden relative">
                <div class="relative z-[1] px-5 md:px-10 py-12 md:py-16 text-center min-h-[520px] md:min-h-[640px] flex flex-col items-center justify-center">
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
                            class="p-5 bg-accent-primary/25 border border-accent-primary/30 text-white rounded-2xl h-12 px-7 text-sm md:text-base font-black hover:bg-accent-primary/30 transition inline-flex items-center justify-center gap-2 leading-none whitespace-nowrap">
                            {{ __('Start Copying Now') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </a>
                        <button type="button" id="btnBecomeLeader"
                            class="p-5 bg-white/5 border border-white/10 text-white rounded-2xl h-12 px-7 text-sm md:text-base font-black hover:bg-white/10 transition inline-flex items-center justify-center gap-2 leading-none whitespace-nowrap">
                            <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                </path>
                            </svg>
                            {{ __('Become a Leader') }}
                        </button>
                    </div>

                    <div class="pb-5 pt-5 mt-9 md:mt-10 flex items-center justify-center gap-3 md:gap-4 flex-wrap">
                        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-xs text-white/70">
                            <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20H4v-2a4 4 0 014-4h1"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="font-black text-white">{{ number_format((int) ($stats['leaders'] ?? 0)) }}+</span>
                            <span class="text-white/55 font-bold">{{ __('Active Leaders') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-xs text-white/70">
                            <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20H4v-2a4 4 0 014-4h1"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="font-black text-white">{{ number_format((int) ($stats['followers'] ?? 0)) }}+</span>
                            <span class="text-white/55 font-bold">{{ __('Total Followers') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-xs text-white/70">
                            <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l3-3 4 4 5-6"></path>
                            </svg>
                            <span class="font-black text-white">${{ $fmtShort($stats['volume'] ?? 0) }}</span>
                            <span class="text-white/55 font-bold">{{ __('Trading Volume') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-xs text-white/70">
                            <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 7-7"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 7h6v6"></path>
                            </svg>
                            <span class="font-black text-white">+{{ $fmt2($stats['top_roi'] ?? 0) }}%</span>
                            <span class="text-white/55 font-bold">{{ __('Top ROI') }}</span>
                        </div>
                    </div>

                    <div class="mt-10 md:mt-12 text-white/50 text-[11px] font-black uppercase tracking-widest inline-flex items-center gap-2">
                        <span class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 grid place-items-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        {{ __('Scroll to explore') }}
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

                <div class="pb-5 pt-5 mt-7 md:mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 text-left">
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

                <div class="pb-5 bt-5 mt-7 md:mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    @forelse ($topLeaders as $pro)
                        @php
                            $name = $pro->display_name ?: ($pro->user->username ?? $pro->user->first_name ?? 'Trader');
                            $followers = (int) ($pro->followers_count ?? 0);
                        @endphp
                        <div class="relative overflow-hidden bg-secondary border border-white/5 rounded-3xl p-5 md:p-6">
                            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-emerald-500/0 via-emerald-400/60 to-emerald-500/0"></div>
                            <div class="absolute -top-16 -left-16 w-40 h-40 rounded-full bg-accent-primary/10 blur-3xl pointer-events-none"></div>

                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-12 h-12 rounded-full bg-white/5 border border-white/10 grid place-items-center text-white font-black">
                                        <span class="relative z-[1]">{{ strtoupper(substr($name, 0, 1)) }}</span>
                                        <div class="absolute inset-0 rounded-full bg-accent-primary/15"></div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-white font-black leading-tight truncate">{{ $name }}</div>
                                        <div class="mt-1 flex items-center gap-2 text-[11px] text-white/55">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                                <span class="font-bold uppercase tracking-widest">{{ __('Verified') }}</span>
                                            </span>
                                            <span class="text-white/25">•</span>
                                            <span class="font-bold uppercase tracking-widest">{{ __('Active') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs px-2.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 font-black uppercase tracking-widest">
                                    {{ __('Leader') }}
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-3 gap-3">
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-3 py-3">
                                    <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-widest font-black text-white/50">
                                        <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 7-7"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 7h6v6"></path>
                                        </svg>
                                        {{ __('ROI') }}
                                    </div>
                                    <div class="mt-1.5 text-emerald-300 font-black leading-tight text-sm md:text-base">+0.0%</div>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-3 py-3">
                                    <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-widest font-black text-white/50">
                                        <svg class="w-3.5 h-3.5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ __('Win Rate') }}
                                    </div>
                                    <div class="mt-1.5 text-white font-black leading-tight text-sm md:text-base">0.0%</div>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-3 py-3">
                                    <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-widest font-black text-white/50">
                                        <svg class="w-3.5 h-3.5 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20H4v-2a4 4 0 014-4h1"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        {{ __('Followers') }}
                                    </div>
                                    <div class="mt-1.5 text-white font-black leading-tight text-sm md:text-base">{{ number_format($followers) }}</div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center justify-between text-[11px] text-white/55">
                                    <span class="font-bold uppercase tracking-widest">{{ __('Capacity') }}</span>
                                    <span class="text-white font-black">{{ number_format($followers) }}/100</span>
                                </div>
                                <div class="mt-2.5 w-full h-2 bg-white/5 rounded-full overflow-hidden">
                                    @php
                                        $capPct = min(100, ($followers / 100) * 100);
                                    @endphp
                                    <div class="h-full bg-accent-primary/60" style="width: {{ $capPct }}%"></div>
                                </div>
                            </div>

                            <div class="mt-6 h-px bg-white/10"></div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <div class="text-[11px] px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/70 font-bold inline-flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-white/5 border border-white/10 grid place-items-center text-white/55 font-black">%</span>
                                    <span class="font-black text-white">0%</span>
                                    <span class="text-white/55 font-bold">{{ __('Profit Share') }}</span>
                                </div>
                        <a href="{{ route('user.trading.copy-trading.profile', ['id' => $pro->id]) }}"
                                    class="text-[13px] font-black text-white hover:text-accent-primary transition inline-flex items-center gap-2">
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
        @elseif ($mode === 'leaders')
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

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <a href="{{ route('user.trading.copy-trading.profile', ['id' => $pro->id]) }}"
                                class="text-[13px] font-black text-white/85 hover:text-accent-primary transition inline-flex items-center gap-2">
                                {{ __('View Profile') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </a>
                        </div>

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
        @else
            @php
                $name = $pro->display_name ?: ($pro->user->username ?? $pro->user->first_name ?? 'Trader');
                $followers = (int) ($stats['followers'] ?? 0);
                $capacityMax = (int) ($profile['capacity_max'] ?? 100);
                $availableSpots = max(0, $capacityMax - $followers);
                $profitShare = (float) ($profile['profit_share_percent'] ?? 0);
                $minInvestmentAmount = (float) ($profile['min_investment_amount'] ?? 0);
                $minInvestmentCurrency = strtoupper((string) ($profile['min_investment_currency'] ?? 'USDT'));
                $isCopying = (bool) $myRelationship;
            @endphp

            <div class="ct-hero ct-profile bg-secondary border border-white/5 rounded-[2.5rem] overflow-hidden relative">
                <div class="px-5 md:px-8 pt-9 pb-10 md:pt-12 md:pb-12">
                    <div class="pt-5 pb-5 md:pt-5 md:pb-5">
                        <a href="{{ route('user.trading.copy-trading.leaders') }}"
                            class="inline-flex items-center gap-2 text-white/70 hover:text-white transition text-sm font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        {{ __('Back to Leaders') }}
                        </a>
                    </div>

                    <div class="pt-5 mt-7 md:mt-8 grid grid-cols-1 lg:grid-cols-3 gap-5 md:gap-6 items-start">
                        <div class="lg:col-span-2">
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 grid place-items-center text-white font-black text-lg relative overflow-hidden">
                                    <span class="relative z-[1]">{{ strtoupper(substr($name, 0, 2)) }}</span>
                                    <div class="absolute inset-0 bg-accent-primary/15"></div>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-white font-black text-2xl md:text-3xl leading-tight">{{ $name }}</div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-3 py-1.5 text-[11px] font-black uppercase tracking-widest text-white/70">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                            {{ strtoupper((string) ($profile['style'] ?? 'SWING')) }}
                                        </span>
                                        <span class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-3 py-1.5 text-[11px] font-black text-emerald-200">
                                            {{ (string) ($profile['risk_level'] ?? 'Conservative') }}
                                        </span>
                                        <span class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-3 py-1.5 text-[11px] font-black uppercase tracking-widest text-white/55">
                                            {{ __('Since') }} {{ $pro->created_at ? $pro->created_at->format('M Y') : __('2025') }}
                                        </span>
                                    </div>
                                    @if ($pro->bio)
                                        <div class="mt-3 text-white/55 text-sm max-w-xl">
                                            {{ $pro->bio }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php
                            $capPct = $capacityMax > 0 ? min(100, ($followers / $capacityMax) * 100) : 0;
                        @endphp

                        <div class="ct-dotted ct-profit-card bg-white/5 border border-white/10 rounded-3xl p-6 md:p-7 relative overflow-hidden">
                            <div class="absolute inset-x-0 top-0 h-1 bg-white/90"></div>

                            <div class="text-center pt-2">
                                <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-[11px] font-black text-white/80">
                                    <span class="w-6 h-6 rounded-lg bg-white/5 border border-white/10 grid place-items-center text-white/60 font-black">%</span>
                                    {{ __('Profit Share') }}
                                </div>
                                <div class="mt-4 text-white font-black text-5xl leading-none">{{ (int) $profitShare }}%</div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3.5">
                                    <div class="text-[10px] uppercase tracking-widest font-black text-white/45">{{ __('Min. Investment') }}</div>
                                    <div class="mt-1.5 text-white font-black text-sm">{{ number_format($minInvestmentAmount, 2) }} {{ $minInvestmentCurrency }}</div>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3.5">
                                    <div class="text-[10px] uppercase tracking-widest font-black text-white/45">{{ __('Available Spots') }}</div>
                                    <div class="mt-1.5 text-white font-black text-sm">{{ number_format($availableSpots) }} / {{ number_format($capacityMax) }}</div>
                                </div>
                            </div>

                            <div class="mt-6 mb-2">
                                <div class="flex items-center justify-between text-[11px] text-white/55">
                                    <span class="font-bold">{{ __('Capacity') }}</span>
                                    <span class="text-white/70 font-bold">{{ number_format($capPct, 0) }}% {{ __('filled') }}</span>
                                </div>
                                <div class="mt-2.5 w-full h-2.5 bg-white/5 rounded-full overflow-hidden relative">
                                    <div class="absolute inset-0 bg-white/10"></div>
                                    <div class="absolute left-0 top-0 h-full bg-accent-primary/65" style="width: {{ $capPct }}%"></div>
                                </div>
                            </div>

                            <div class="mt-8 pb-2">
                                <select class="copy-market hidden" data-pro-id="{{ $pro->id }}">
                                    <option value="both" selected></option>
                                </select>
                                <select class="copy-allocation-type hidden" data-pro-id="{{ $pro->id }}">
                                    <option value="percent" selected></option>
                                </select>
                                <input type="number" class="copy-allocation-value hidden" data-pro-id="{{ $pro->id }}" value="10" />
                                <input type="number" class="copy-leverage hidden" data-pro-id="{{ $pro->id }}" value="50" />
                                <select class="copy-margin-mode hidden" data-pro-id="{{ $pro->id }}">
                                    <option value="normal" selected></option>
                                </select>

                                @if ($isCopying)
                                    <button type="button" data-pro-id="{{ $pro->id }}"
                                        class="btn-unfollow w-full bg-red-500/15 border border-red-500/25 text-red-200 rounded-2xl px-6 py-4 text-sm font-black hover:bg-red-500/20 transition inline-flex items-center justify-center gap-2">
                                        {{ __('Stop Copying') }}
                                    </button>
                                @else
                                    <button type="button" data-pro-id="{{ $pro->id }}"
                                        class="btn-follow w-full bg-white text-black rounded-2xl px-6 py-4 text-sm font-black hover:bg-white/90 transition inline-flex items-center justify-center gap-3">
                                        <svg class="w-4 h-4 text-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-7 7h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ __('Start Copying') }}
                                    </button>
                                @endif
                                <a href="{{ route('user.trading.copy-trading.leaders') }}"
                                    class="mt-4 block text-center text-xs font-black uppercase tracking-widest text-white/55 hover:text-white transition">
                                    {{ __('Advanced settings') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>

            <div class="mt-10 md:mt-12 grid grid-cols-1 lg:grid-cols-3 gap-5 md:gap-6">
                <div class="lg:col-span-2">
                    <div class="text-white font-black text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m3 0V9a2 2 0 00-2-2H8a2 2 0 00-2 2v8m16 0H4"></path>
                        </svg>
                        {{ __('Performance Metrics') }}
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 7-7"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 7h6v6"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-emerald-300 font-black text-xl">+{{ number_format((float) ($stats['roi'] ?? 0), 2) }}%</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Total ROI') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ number_format((float) ($stats['win_rate'] ?? 0), 2) }}%</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Win Rate') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20H4v-2a4 4 0 014-4h1"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ number_format((int) ($stats['followers'] ?? 0)) }}</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Followers') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l3-3 4 4 5-6"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ number_format((int) ($stats['total_trades'] ?? 0)) }}</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Total Trades') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-emerald-300 font-black text-xl">{{ number_format((float) ($stats['total_profit'] ?? 0), 2) }} USDT</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Total profit') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-7 7h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ number_format((float) ($stats['total_volume'] ?? 0), 2) }} USDT</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Total volume') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m8-10v10M3 9v12"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ number_format((float) ($stats['avg_profit_per_trade'] ?? 0), 2) }} USDT</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Avg. profit/trade') }}</div>
                        </div>

                        <div class="bg-secondary border border-white/5 rounded-3xl px-6 py-6 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 mx-auto grid place-items-center">
                                <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-4 text-white font-black text-xl">{{ ($stats['max_drawdown'] === null) ? 'N/A' : number_format((float) $stats['max_drawdown'], 2) . '%' }}</div>
                            <div class="mt-1 text-white/45 text-xs font-bold">{{ __('Max Drawdown') }}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-2xl px-3 py-2 text-xs font-black text-white/70">
                            <span class="bg-white/10 rounded-xl px-3 py-1">{{ __('Recent Trades') }}</span>
                        
                        </div>

                        <div class="mt-6 bg-secondary border border-white/5 rounded-3xl p-10 md:p-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mx-auto grid place-items-center">
                                <svg class="w-8 h-8 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12h2l3 7 4-14 3 7h6"></path>
                                </svg>
                            </div>
                            <div class="mt-5 text-white font-black text-xl">{{ __('No Recent Trades') }}</div>
                            <div class="mt-2 text-white/55">{{ __("This leader hasn't completed any trades yet.") }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-secondary border border-white/5 rounded-3xl p-6 md:p-7 h-fit relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/5 via-white/0 to-white/5 opacity-60 pointer-events-none"></div>
                    <div class="text-white font-black text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ __('Quick Info') }}
                    </div>

                    <div class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-white/60 flex items-center gap-2">
                                <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 7-7"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 7h6v6"></path>
                                </svg>
                                {{ __('Style') }}
                            </div>
                            <div class="text-white font-black">{{ strtoupper((string) ($profile['style'] ?? 'SWING')) }}</div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-white/60 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ __('Risk Level') }}
                            </div>
                            <div class="text-emerald-300 font-black">{{ (string) ($profile['risk_level'] ?? 'Conservative') }}</div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-white/60 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-lg bg-white/5 border border-white/10 grid place-items-center text-white/55 font-black text-xs">%</span>
                                {{ __('Profit Share') }}
                            </div>
                            <div class="text-white font-black">{{ (int) $profitShare }}%</div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-white/60 flex items-center gap-2">
                                <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ __('Min. Investment') }}
                            </div>
                            <div class="text-white font-black">{{ number_format($minInvestmentAmount, 2) }} {{ $minInvestmentCurrency }}</div>
                        </div>
                    </div>
                </div>
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

