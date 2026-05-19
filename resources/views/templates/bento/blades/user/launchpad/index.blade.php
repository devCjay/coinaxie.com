@extends('templates.bento.blades.layouts.user')

@section('content')
    @php
        $fmt8 = fn($v) => rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.');
        $feeText = ((float) $feeAmount > 0) ? ($fmt8($feeAmount) . ' ' . strtoupper($feeCurrency)) : __('Free');
    @endphp

    <style>
        .lp-hero {
            background: radial-gradient(1200px 600px at 50% 0%, rgba(16, 185, 129, 0.16), rgba(2, 6, 23, 0)),
                radial-gradient(900px 420px at 20% 30%, rgba(139, 92, 246, 0.12), rgba(2, 6, 23, 0)),
                radial-gradient(900px 420px at 80% 30%, rgba(59, 130, 246, 0.10), rgba(2, 6, 23, 0));
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
            max-width: 900px;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.55);
        }
    </style>

    <div class="min-h-screen px-2 md:px-0">
        <div class="lp-hero bg-secondary border border-white/5 rounded-[2.5rem] overflow-hidden relative">
            <div class="px-6 md:px-10 py-10 md:py-14 text-center">
                <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white/70">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent-primary"></span>
                    {{ __('The Regulated ITO Platform') }}
                </div>

                <h1 class="mt-6 text-3xl md:text-5xl font-black tracking-tight text-white">
                    {{ __('The Future of') }}
                    <span class="text-accent-primary">{{ __('Token Offerings') }}</span>
                </h1>
                <p class="mt-4 text-white/60 max-w-2xl mx-auto text-sm md:text-base leading-relaxed">
                    {{ __('Discover, invest, and launch tokens on the most trusted ITO platform. Secure, transparent, and regulated.') }}
                </p>

                <div class="mt-8 md:mt-9 flex items-center justify-center gap-4 md:gap-5 flex-wrap">
                    <a href="#lp-explore"
                        class="bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-6 py-3 text-sm font-semibold hover:bg-accent-primary/25 transition">
                        {{ __('Explore Offerings') }}
                    </a>
                    <button type="button" onclick="openModal('launchProjectModal')"
                        class="bg-white/5 border border-white/10 text-white rounded-xl px-6 py-3 text-sm font-semibold hover:bg-white/10 transition">
                        {{ __('Launch Your Project') }}
                    </button>
                </div>

                <div class="mt-8 flex items-center justify-center gap-4 md:gap-5 flex-wrap">
                    <div class="bg-white/5 border border-white/10 rounded-full px-5 py-2.5 text-xs text-white/70">
                        <span class="font-semibold text-white">{{ number_format((int) ($stats['projects'] ?? 0)) }}</span>
                        <span class="text-white/50">{{ __('Projects') }}</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-full px-5 py-2.5 text-xs text-white/70">
                        <span class="font-semibold text-white">{{ number_format((int) ($stats['investors'] ?? 0)) }}</span>
                        <span class="text-white/50">{{ __('Investors') }}</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-full px-5 py-2.5 text-xs text-white/70">
                        <span class="font-semibold text-white">{{ $fmt8($stats['total_raised'] ?? 0) }}</span>
                        <span class="text-white/50">{{ __('Total Raised') }}</span>
                    </div>
                </div>

                <div class="mt-10 text-white/40 text-[10px] font-bold uppercase tracking-widest">
                    {{ __('Scroll to explore') }}
                </div>
            </div>
        </div>

        <div id="lp-explore" class="mt-8 md:mt-10">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white/70">
                    <svg class="w-4 h-4 text-accent-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('Success Stories') }}
                </div>
                <h2 class="mt-5 text-2xl md:text-4xl font-black tracking-tight text-white">
                    {{ __('Recently Funded') }}
                    <span class="text-accent-primary">{{ __('Projects') }}</span>
                </h2>
                <p class="mt-3 text-white/55 text-sm">{{ __('See the projects that reached their funding goals, plus active offerings.') }}</p>

                <div class="mt-6 max-w-xl mx-auto bg-white/5 border border-white/10 rounded-2xl px-5 py-4 flex items-center justify-center gap-6 text-sm text-white/70 flex-wrap">
                    <div>
                        <span class="text-white/50 text-xs uppercase tracking-widest font-bold">{{ __('Total Raised') }}</span>
                        <span class="ml-2 text-accent-primary font-black">{{ $fmt8($stats['total_raised'] ?? 0) }}</span>
                    </div>
                    <div class="h-5 w-px bg-white/10 hidden md:block"></div>
                    <div>
                        <span class="text-white/50 text-xs uppercase tracking-widest font-bold">{{ __('Investors') }}</span>
                        <span class="ml-2 text-white font-black">{{ number_format((int) ($stats['investors'] ?? 0)) }}</span>
                    </div>
                    <div class="h-5 w-px bg-white/10 hidden md:block"></div>
                    <div>
                        <span class="text-white/50 text-xs uppercase tracking-widest font-bold">{{ __('Avg') }}</span>
                        <span class="ml-2 text-white font-black">{{ number_format((float) ($stats['avg_funded'] ?? 0), 2) }}%</span>
                        <span class="text-white/50 text-xs uppercase tracking-widest font-bold ml-1">{{ __('funded') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
                @forelse ($projects as $project)
                    @php
                        $investors = (int) ($investorsByProject[$project->id] ?? 0);
                        $hardCap = (float) $project->hard_cap_quote;
                        $sold = (float) $project->sold_quote;
                        $fundedPct = $hardCap > 0 ? min(100.0, ($sold / $hardCap) * 100.0) : 0.0;
                        $isFunded = ($hardCap > 0 && $sold >= $hardCap) || in_array($project->status, ['ended', 'launched'], true);
                        $chipClass = $isFunded ? 'bg-green-500/15 border border-green-500/25 text-green-400' : 'bg-white/5 border border-white/10 text-white/55';
                        $chipText = $isFunded ? __('Funded') : __('Active');
                        $durationDays = null;
                        if ($project->sale_start_at && $project->sale_end_at) {
                            $durationDays = $project->sale_start_at->diffInDays($project->sale_end_at);
                        }
                    @endphp
                    <div class="bg-secondary border border-white/5 rounded-3xl p-5 relative overflow-hidden">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                @if ($project->token_logo_url)
                                    <img src="{{ $project->token_logo_url }}" alt="{{ $project->token_symbol }}"
                                        class="w-11 h-11 rounded-2xl object-cover border border-white/10">
                                @else
                                    <div class="w-11 h-11 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white font-black uppercase">
                                        {{ strtoupper(substr((string) $project->token_symbol, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-white font-black leading-tight">{{ $project->name }}</div>
                                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60 mt-0.5">
                                        {{ strtoupper($project->token_symbol) }} • {{ strtoupper($project->quote_currency) }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs px-2 py-1 rounded-full {{ $chipClass }}">{{ $chipText }}</div>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-white/55">
                                <span>{{ __('Funding') }}</span>
                                <span class="text-accent-primary font-bold">{{ number_format($fundedPct, 0) }}%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-white/5 overflow-hidden">
                                <div class="h-2 rounded-full bg-accent-primary/70" style="width: {{ $fundedPct }}%"></div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-4">
                                <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60">{{ __('Raised') }}</div>
                                <div class="mt-1 text-white font-black">{{ $fmt8($project->sold_quote) }}</div>
                            </div>
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-4">
                                <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60">{{ __('Investors') }}</div>
                                <div class="mt-1 text-white font-black">{{ number_format($investors) }}</div>
                            </div>
                        </div>

                        <div class="mt-4 text-xs text-white/55 flex items-center justify-between">
                            <div>
                                {{ __('Sale Price') }}:
                                <span class="text-white font-semibold">{{ $fmt8($project->sale_price) }}</span>
                            </div>
                            <div>
                                @if (!is_null($durationDays))
                                    {{ __('Completed in') }} <span class="text-white font-semibold">{{ $durationDays }}</span> {{ __('days') }}
                                @else
                                    <span class="text-white/35">—</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 flex items-center gap-2">
                            <a href="{{ route('user.launchpad.show', $project->slug) }}"
                                class="flex-1 bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-white/10 transition text-center">
                                {{ __('View') }}
                            </a>
                            @if ($project->trading_enabled)
                                <a href="{{ route('user.launchpad.trade', $project->slug) }}"
                                    class="flex-1 bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-accent-primary/25 transition text-center">
                                    {{ __('Trade') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-secondary border border-white/5 rounded-3xl p-8 text-center text-white/50 md:col-span-2 xl:col-span-4">
                        {{ __('No launchpad projects yet.') }}
                    </div>
                @endforelse
            </div>

            <div class="mt-10 bg-secondary border border-white/5 rounded-3xl p-5">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <div class="text-white font-black">{{ __('My Project Launches') }}</div>
                        <div class="text-white/55 text-sm">{{ __('Projects you submitted. Pending projects are hidden until approved.') }}</div>
                    </div>
                    <button type="button" onclick="openModal('launchProjectModal')"
                        class="bg-white/5 border border-white/10 text-white rounded-xl px-5 py-2.5 text-sm font-semibold hover:bg-white/10 transition">
                        {{ __('Launch Your Project') }}
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($myProjects as $mp)
                        @php
                            $mpChip = match ($mp->approval_status) {
                                'approved' => 'bg-green-500/15 border border-green-500/25 text-green-400',
                                'rejected' => 'bg-red-500/15 border border-red-500/25 text-red-300',
                                default => 'bg-white/5 border border-white/10 text-white/55',
                            };
                        @endphp
                        <div class="bg-white/5 border border-white/10 rounded-2xl p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-white font-semibold">{{ $mp->name }}</div>
                                    <div class="text-white/55 text-xs mt-1">{{ strtoupper($mp->token_symbol) }}/{{ strtoupper($mp->quote_currency) }}</div>
                                </div>
                                <div class="text-xs px-2 py-1 rounded-full {{ $mpChip }}">{{ ucfirst($mp->approval_status) }}</div>
                            </div>
                            <div class="mt-3 text-xs text-white/55">
                                {{ __('Fee') }}:
                                <span class="text-white font-semibold">{{ $mp->launch_fee_amount > 0 ? ($fmt8($mp->launch_fee_amount) . ' ' . strtoupper($mp->launch_fee_currency)) : __('Free') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-white/50 text-sm">{{ __('No submissions yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div id="launchProjectModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-white">{{ __('Launch Your Project') }}</h3>
                    <p class="text-white/55 text-sm mt-1">
                        {{ __('Launch fee') }}:
                        <span class="text-white font-semibold">{{ $feeText }}</span>
                        <span class="text-white/45">•</span>
                        <span class="text-white/55">{{ __('Balance') }}:</span>
                        <span class="text-white font-semibold">{{ $fmt8((float) ($feeAccount->balance ?? 0)) }} {{ strtoupper($feeCurrency) }}</span>
                    </p>
                </div>
                <button onclick="closeModal('launchProjectModal')" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="launchProjectForm">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Name') }}</label>
                        <input type="text" name="name" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Symbol') }}</label>
                        <input type="text" name="token_symbol" required placeholder="ABC"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Quote Currency') }}</label>
                        <input type="text" name="quote_currency" value="USDT" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale Price') }}</label>
                        <input type="number" step="0.00000001" min="0" name="sale_price" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Hard Cap (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="hard_cap_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Min Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="min_buy_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Max Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="max_buy_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Name') }}</label>
                        <input type="text" name="token_name"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Decimals') }}</label>
                        <input type="number" min="0" max="18" name="token_decimals" value="8"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Logo URL') }}</label>
                        <input type="text" name="token_logo_url" placeholder="https://..."
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Description') }}</label>
                        <textarea name="description" rows="3"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all resize-none"></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale Start At') }}</label>
                        <input type="datetime-local" name="sale_start_at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale End At') }}</label>
                        <input type="datetime-local" name="sale_end_at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Launch At') }}</label>
                        <input type="datetime-local" name="launch_at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-white/5 gap-3">
                    <button type="button" onclick="closeModal('launchProjectModal')"
                        class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-white transition-all">{{ __('Cancel') }}</button>
                    <button type="submit" id="submitLaunchProjectBtn"
                        class="px-8 py-3 bg-accent-primary text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:opacity-90 transition-all flex items-center gap-2">
                        {{ __('Submit for Approval') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal(id) {
            $('#' + id).fadeIn(200);
        }

        function closeModal(id) {
            $('#' + id).fadeOut(200);
        }

        $(document).ready(function() {
            $(document).on('click', '.modal', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $('.modal').fadeOut(200);
                }
            });

            $('#launchProjectForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#submitLaunchProjectBtn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('user.launchpad.submit') }}",
                    method: 'POST',
                    data: $(this).serialize(),
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
