@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    @php
        $fmt8 = fn($v) => rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.');
        $sort = request('sort', 'created_at');
        $dir = request('dir', 'desc');
        $nextDir = fn($col) => ($sort === $col && strtolower((string) $dir) === 'asc') ? 'desc' : 'asc';
        $sortUrl = fn($col) => route('admin.launchpad.index', array_merge(request()->except('page'), ['sort' => $col, 'dir' => $nextDir($col)]));
    @endphp

    <style>
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
            margin: 5% auto;
            padding: 28px;
            width: 92%;
            max-width: 900px;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.55);
        }
    </style>

    <div id="launchpad-content" class="space-y-8">
        <div id="stats-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['unique_tokens'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Unique Tokens') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5v14"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['launched_projects'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Tokens Launched') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-accent-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-accent-primary/10 flex items-center justify-center text-accent-primary mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ $fmt8($stats['total_sales_quote'] ?? 0) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Total Sales (Quote)') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-violet-500/10 flex items-center justify-center text-violet-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0Z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['unique_holders'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Unique Holders') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3v18h18"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 14l3-3 2 2 5-5"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ $fmt8($stats['total_holdings'] ?? 0) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Total Holdings') }}</div>
                </div>
            </div>
        </div>

        <div id="launchpad-wrapper" class="bg-secondary border border-white/5 rounded-[2rem] overflow-hidden shadow-2xl relative">
            <div class="bg-secondary/30 border-b border-white/5 p-4 lg:p-6 relative">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-text-secondary border border-white/10 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold tracking-tight">{{ __('Launchpad Projects') }}</h4>
                            <p class="text-[10px] text-text-secondary uppercase font-bold tracking-widest mt-0.5 opacity-50">
                                {{ __('Create, manage, finalize sales and enable trading') }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.launchpad.index') }}" class="flex-1 max-w-2xl flex flex-wrap gap-3 lg:justify-end">
                        <div class="relative flex-1 group min-w-[200px]">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ __('Search by project name, token or status...') }}"
                                class="w-full h-12 bg-white/5 border border-white/10 rounded-2xl px-5 text-sm font-medium text-white focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-all outline-none placeholder:text-text-secondary/30">
                            <button type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 hover:bg-white/10 rounded-xl text-text-secondary transition-colors group-hover:text-accent-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>

                        @if (request('search'))
                            <a href="{{ route('admin.launchpad.index') }}"
                                class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-text-secondary hover:text-white flex items-center gap-2 transition-all font-bold text-xs uppercase tracking-widest shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                {{ __('Clear') }}
                            </a>
                        @endif

                        @php
                            $lpFeeAmount = (float) getSetting('launchpad_launch_fee_amount', 0);
                            $lpFeeCurrency = strtoupper((string) getSetting('launchpad_launch_fee_currency', 'USDT'));
                            $lpFeeText = $lpFeeAmount > 0 ? ($fmt8($lpFeeAmount) . ' ' . $lpFeeCurrency) : __('Free');
                        @endphp

                        <button type="button" onclick="openModal('launchFeeModal')"
                            class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-text-secondary hover:text-white flex items-center gap-2 transition-all font-bold text-xs uppercase tracking-widest shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('Launch Fee') }}: {{ $lpFeeText }}
                        </button>

                        <button type="button" onclick="openModal('createLaunchpadModal')"
                            class="h-12 px-6 rounded-2xl bg-accent-primary text-white flex items-center gap-2 transition-all font-black text-xs uppercase tracking-widest shadow-lg hover:opacity-90 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ __('Launch Project') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/[0.02]">
                            <th class="px-8 py-6 text-left">
                                <a href="{{ $sortUrl('name') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Project') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <a href="{{ $sortUrl('sale_price') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Sale Price') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <a href="{{ $sortUrl('sold_quote') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Total Sales') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <a href="{{ $sortUrl('sold_tokens') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Tokens Sold') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <a href="{{ $sortUrl('status') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Status') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <a href="{{ $sortUrl('created_at') }}"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60 hover:opacity-100 transition">
                                    {{ __('Created') }}
                                </a>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Action') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($projects as $p)
                            @php
                                $badgeClass = match ($p->status) {
                                    'live' => 'bg-green-500/15 border border-green-500/25 text-green-400',
                                    'launched' => 'bg-blue-500/15 border border-blue-500/25 text-blue-400',
                                    'ended' => 'bg-amber-500/15 border border-amber-500/25 text-amber-400',
                                    'canceled' => 'bg-red-500/15 border border-red-500/25 text-red-300',
                                    default => 'bg-white/5 border border-white/10 text-white/55',
                                };
                                $statusLabel = match ((string) $p->status) {
                                    'draft' => __('Presale'),
                                    'live' => __('Live'),
                                    'ended' => __('Ended'),
                                    'launched' => __('Launched'),
                                    'canceled' => __('Canceled'),
                                    default => ucfirst((string) $p->status),
                                };
                                $projectPayload = [
                                    'id' => $p->id,
                                    'name' => $p->name,
                                    'token_symbol' => strtoupper($p->token_symbol),
                                    'token_name' => $p->token_name,
                                    'token_decimals' => (int) ($p->token_decimals ?? 8),
                                    'token_logo_url' => $p->token_logo_url,
                                    'description' => $p->description,
                                    'quote_currency' => strtoupper($p->quote_currency),
                                    'sale_price' => (float) $p->sale_price,
                                    'hard_cap_quote' => (float) $p->hard_cap_quote,
                                    'min_buy_quote' => (float) $p->min_buy_quote,
                                    'max_buy_quote' => (float) $p->max_buy_quote,
                                    'sale_start_at' => $p->sale_start_at ? $p->sale_start_at->format('Y-m-d\\TH:i') : '',
                                    'sale_end_at' => $p->sale_end_at ? $p->sale_end_at->format('Y-m-d\\TH:i') : '',
                                    'launch_at' => $p->launch_at ? $p->launch_at->format('Y-m-d\\TH:i') : '',
                                    'status' => $p->status,
                                ];
                            @endphp
                            <tr class="group hover:bg-white/[0.01] transition-colors relative">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        @if ($p->token_logo_url)
                                            <img src="{{ $p->token_logo_url }}" alt="{{ $p->token_symbol }}"
                                                class="w-10 h-10 rounded-xl object-cover border border-white/10">
                                        @else
                                            <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white font-black uppercase">
                                                {{ strtoupper(substr((string) $p->token_symbol, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="text-sm font-black text-white leading-tight truncate">
                                                {{ $p->name }}
                                            </div>
                                            <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-50 mt-0.5">
                                                {{ strtoupper($p->token_symbol) }}/{{ strtoupper($p->quote_currency) }}
                                            </div>
                                            <div class="text-[10px] text-text-secondary opacity-50 mt-1">
                                                {{ __('Trading') }}: {{ $p->trading_enabled ? __('Enabled') : __('Disabled') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right font-mono text-sm">
                                    <div class="text-white font-black">{{ $fmt8($p->sale_price) }}</div>
                                    <div class="text-[9px] text-text-secondary font-bold uppercase opacity-40">{{ strtoupper($p->quote_currency) }}</div>
                                </td>
                                <td class="px-8 py-5 text-right font-mono text-sm">
                                    <div class="text-white font-black">{{ $fmt8($p->sold_quote) }}</div>
                                    <div class="text-[9px] text-text-secondary font-bold uppercase opacity-40">{{ strtoupper($p->quote_currency) }}</div>
                                </td>
                                <td class="px-8 py-5 text-right font-mono text-sm">
                                    <div class="text-white font-black">{{ $fmt8($p->sold_tokens) }}</div>
                                    <div class="text-[9px] text-text-secondary font-bold uppercase opacity-40">{{ strtoupper($p->token_symbol) }}</div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-xs px-2 py-1 rounded-full inline-block {{ $badgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if (($p->approval_status ?? 'approved') !== 'approved')
                                        <div class="text-white/55 text-xs mt-2">
                                            {{ __('Approval') }}:
                                            <span class="text-white font-semibold">{{ ucfirst((string) $p->approval_status) }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-8 py-5 text-right text-sm text-white/70">
                                    <div class="font-mono">{{ $p->created_at ? $p->created_at->format('Y-m-d') : '—' }}</div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <button type="button"
                                            class="btn-edit bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2 font-semibold hover:bg-white/10 transition"
                                            data-project='@json($projectPayload)'>
                                            {{ __('Edit') }}
                                        </button>

                                        @if (($p->approval_status ?? 'approved') === 'pending')
                                            <form action="{{ route('admin.launchpad.approve') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $p->id }}">
                                                <button class="bg-green-500/15 border border-green-500/25 text-green-300 rounded-xl px-4 py-2 font-semibold hover:bg-green-500/20 transition">
                                                    {{ __('Approve') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.launchpad.reject') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $p->id }}">
                                                <button class="bg-red-500/15 border border-red-500/25 text-red-300 rounded-xl px-4 py-2 font-semibold hover:bg-red-500/20 transition">
                                                    {{ __('Reject') }}
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.launchpad.finalize') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $p->id }}">
                                            <button class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2 font-semibold hover:bg-white/10 transition">
                                                {{ __('Finalize') }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.launchpad.enable-trading') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $p->id }}">
                                            <button class="bg-green-500/15 border border-green-500/25 text-green-300 rounded-xl px-4 py-2 font-semibold hover:bg-green-500/20 transition">
                                                {{ __('Enable Trading') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-8 py-16 text-center text-text-secondary">
                                    {{ __('No launchpad projects yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-5">
                {{ $projects->links() }}
            </div>
        </div>
    </div>

    <div id="createLaunchpadModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-bold text-white">{{ __('Launch Project') }}</h3>
                <button onclick="closeModal('createLaunchpadModal')" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.launchpad.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Name') }}</label>
                        <input type="text" name="name" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Symbol') }}</label>
                        <input type="text" name="token_symbol" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="ABC">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Quote Currency') }}</label>
                        <input type="text" name="quote_currency" value="USDT" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="USDT">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale Price') }}</label>
                        <input type="number" step="0.00000001" min="0" name="sale_price" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="0.01">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Hard Cap (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="hard_cap_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="0">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Min Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="min_buy_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="0">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Max Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="max_buy_quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="0">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Name') }}</label>
                        <input type="text" name="token_name"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="{{ __('Optional') }}">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Decimals') }}</label>
                        <input type="number" min="0" max="18" name="token_decimals" value="8"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Logo URL') }}</label>
                        <input type="text" name="token_logo_url"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="https://...">
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Description') }}</label>
                        <textarea name="description" rows="3"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all resize-none"
                            placeholder="{{ __('Optional') }}"></textarea>
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
                    <button type="button" onclick="closeModal('createLaunchpadModal')"
                        class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-white transition-all">{{ __('Cancel') }}</button>
                    <button type="submit"
                        class="px-8 py-3 bg-accent-primary text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:opacity-90 transition-all flex items-center gap-2">
                        {{ __('Create Project') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editLaunchpadModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-bold text-white">{{ __('Edit Project') }}</h3>
                <button onclick="closeModal('editLaunchpadModal')" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.launchpad.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit-id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Name') }}</label>
                        <input type="text" name="name" id="edit-name" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Symbol') }}</label>
                        <input type="text" id="edit-token-symbol" disabled
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white/60 focus:outline-none transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Quote Currency') }}</label>
                        <input type="text" name="quote_currency" id="edit-quote-currency" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale Price') }}</label>
                        <input type="number" step="0.00000001" min="0" name="sale_price" id="edit-sale-price" required
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Hard Cap (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="hard_cap_quote" id="edit-hard-cap-quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Min Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="min_buy_quote" id="edit-min-buy-quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Max Buy (Quote)') }}</label>
                        <input type="number" step="0.00000001" min="0" name="max_buy_quote" id="edit-max-buy-quote"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Status') }}</label>
                        <select name="status" id="edit-status"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all appearance-none cursor-pointer">
                            <option value="draft" class="bg-[#0f172a]">{{ __('Presale') }}</option>
                            <option value="live" class="bg-[#0f172a]">{{ __('Live') }}</option>
                            <option value="ended" class="bg-[#0f172a]">{{ __('Ended') }}</option>
                            <option value="launched" class="bg-[#0f172a]">{{ __('Launched') }}</option>
                            <option value="canceled" class="bg-[#0f172a]">{{ __('Canceled') }}</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Name') }}</label>
                        <input type="text" name="token_name" id="edit-token-name"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Decimals') }}</label>
                        <input type="number" min="0" max="18" name="token_decimals" id="edit-token-decimals"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Token Logo URL') }}</label>
                        <input type="text" name="token_logo_url" id="edit-token-logo-url"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all"
                            placeholder="https://...">
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Description') }}</label>
                        <textarea name="description" rows="3" id="edit-description"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all resize-none"></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale Start At') }}</label>
                        <input type="datetime-local" name="sale_start_at" id="edit-sale-start-at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Sale End At') }}</label>
                        <input type="datetime-local" name="sale_end_at" id="edit-sale-end-at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Launch At') }}</label>
                        <input type="datetime-local" name="launch_at" id="edit-launch-at"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-white/5 gap-3">
                    <button type="button" onclick="closeModal('editLaunchpadModal')"
                        class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-white transition-all">{{ __('Cancel') }}</button>
                    <button type="submit"
                        class="px-8 py-3 bg-accent-primary text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:opacity-90 transition-all flex items-center gap-2">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="launchFeeModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-bold text-white">{{ __('Launch Fee') }}</h3>
                <button onclick="closeModal('launchFeeModal')" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.launchpad.launch-fee') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Fee Amount') }}</label>
                        <input type="number" step="0.00000001" min="0" name="fee_amount" value="{{ (float) getSetting('launchpad_launch_fee_amount', 0) }}"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Fee Currency') }}</label>
                        <input type="text" name="fee_currency" value="{{ strtoupper((string) getSetting('launchpad_launch_fee_currency', 'USDT')) }}"
                            class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent-primary transition-all">
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-white/5 gap-3">
                    <button type="button" onclick="closeModal('launchFeeModal')"
                        class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-white transition-all">{{ __('Cancel') }}</button>
                    <button type="submit"
                        class="px-8 py-3 bg-accent-primary text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:opacity-90 transition-all">
                        {{ __('Save') }}
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
            $(document).on('click', '.btn-edit', function() {
                const raw = $(this).attr('data-project');
                let p = null;
                try {
                    p = JSON.parse(raw);
                } catch (e) {
                    p = null;
                }
                if (!p) {
                    return;
                }

                $('#edit-id').val(p.id || '');
                $('#edit-name').val(p.name || '');
                $('#edit-token-symbol').val(p.token_symbol || '');
                $('#edit-token-name').val(p.token_name || '');
                $('#edit-token-decimals').val(p.token_decimals ?? 8);
                $('#edit-token-logo-url').val(p.token_logo_url || '');
                $('#edit-description').val(p.description || '');
                $('#edit-quote-currency').val(p.quote_currency || 'USDT');
                $('#edit-sale-price').val(p.sale_price ?? '');
                $('#edit-hard-cap-quote').val(p.hard_cap_quote ?? 0);
                $('#edit-min-buy-quote').val(p.min_buy_quote ?? 0);
                $('#edit-max-buy-quote').val(p.max_buy_quote ?? 0);
                $('#edit-sale-start-at').val(p.sale_start_at || '');
                $('#edit-sale-end-at').val(p.sale_end_at || '');
                $('#edit-launch-at').val(p.launch_at || '');
                $('#edit-status').val(p.status || 'draft');

                openModal('editLaunchpadModal');
            });

            $(document).on('click', '.modal', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $('.modal').fadeOut(200);
                }
            });
        });
    </script>
@endsection
