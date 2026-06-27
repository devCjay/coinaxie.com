@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    @php
        $fmt2 = fn($v) => number_format((float) $v, 2, '.', '');
        $fmt8 = fn($v) => rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.');
        $minCopyAmount = (float) ($minCopyAmount ?? 0);
        $stats = $stats ?? [];
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

    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0Z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['pro_traders'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Pro Traders') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['active_pro_traders'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Active Pros') }}</div>
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
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['active_relationships'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Active Followers') }}</div>
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
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['total_followers'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Total Followers') }}</div>
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
                    <div class="text-3xl font-black text-white leading-none">{{ $fmt2($minCopyAmount) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Min Copy (USDT)') }}</div>
                </div>
            </div>
        </div>

        <div class="bg-secondary border border-white/5 rounded-[2rem] overflow-hidden shadow-2xl relative">
            <div class="bg-secondary/30 border-b border-white/5 p-4 lg:p-6 relative">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-text-secondary border border-white/10 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold tracking-tight">{{ __('Pro Traders') }}</h4>
                            <p class="text-[10px] text-text-secondary uppercase font-bold tracking-widest mt-0.5 opacity-50">
                                {{ __('Create, manage and approve traders for copy trading') }}</p>
                        </div>
                    </div>

                    <div class="flex-1 max-w-2xl flex flex-wrap gap-3 lg:justify-end">
                        <div class="relative flex-1 group min-w-[200px]">
                            <input type="text" id="proSearch" placeholder="{{ __('Search by user, display name or status...') }}"
                                class="w-full h-12 bg-white/5 border border-white/10 rounded-2xl px-5 text-sm font-medium text-white focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-all outline-none placeholder:text-text-secondary/30">
                            <button type="button" id="clearProSearch"
                                class="hidden absolute right-3 top-1/2 -translate-y-1/2 p-2 hover:bg-white/10 rounded-xl text-text-secondary transition-colors group-hover:text-accent-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <button type="button" onclick="openModal('minCopyAmountModal')"
                            class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-text-secondary hover:text-white flex items-center gap-2 transition-all font-bold text-xs uppercase tracking-widest shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('Min Copy') }}: {{ $fmt2($minCopyAmount) }} {{ __('USDT') }}
                        </button>

                        <a href="{{ route('admin.copy-trading.relationships.index') }}"
                            class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-text-secondary hover:text-white flex items-center gap-2 transition-all font-bold text-xs uppercase tracking-widest shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0Z"></path>
                            </svg>
                            {{ __('Relationships') }}
                        </a>

                        <button type="button" onclick="openModal('createProModal')"
                            class="h-12 px-6 rounded-2xl bg-accent-primary text-white flex items-center gap-2 transition-all font-black text-xs uppercase tracking-widest shadow-lg hover:opacity-90 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ __('Add Pro Trader') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/[0.02]">
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Trader') }}</span>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Followers') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Status') }}</span>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Created') }}</span>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Action') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5" id="prosTbody">
                        @forelse ($pros as $pro)
                            @php
                                $userLabel = $pro->user->username ?? $pro->user->email ?? ('#' . $pro->user_id);
                                $initials = strtoupper(substr((string) $userLabel, 0, 2));
                                $displayName = (string) ($pro->display_name ?: $userLabel);
                                $searchHay = strtolower($displayName . ' ' . $userLabel . ' ' . (string) $pro->status);
                                $badgeClass = $pro->status === 'active'
                                    ? 'bg-green-500/15 border border-green-500/25 text-green-400'
                                    : 'bg-white/5 border border-white/10 text-white/55';
                                $payload = [
                                    'id' => (int) $pro->id,
                                    'user_label' => $userLabel,
                                    'display_name' => (string) ($pro->display_name ?? ''),
                                    'bio' => (string) ($pro->bio ?? ''),
                                    'style' => (string) ($pro->style ?? ''),
                                    'risk_level' => (string) ($pro->risk_level ?? ''),
                                    'profit_share_percent' => (float) ($pro->profit_share_percent ?? 0),
                                    'min_investment_amount' => (float) ($pro->min_investment_amount ?? 0),
                                    'min_investment_currency' => (string) ($pro->min_investment_currency ?? 'USDT'),
                                    'status' => (string) ($pro->status ?? 'inactive'),
                                ];
                            @endphp

                            <tr class="pro-row" data-search="{{ e($searchHay) }}">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 grid place-items-center text-white/70 font-black text-sm">
                                            {{ $initials }}
                                        </div>
                                        <div class="min-w-[180px]">
                                            <div class="text-white font-bold tracking-tight">{{ $displayName }}</div>
                                            <div class="text-xs text-text-secondary mt-1">{{ $userLabel }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-right">
                                    <div class="text-white font-black">{{ number_format((int) ($pro->followers_count ?? 0)) }}</div>
                                </td>

                                <td class="px-8 py-6">
                                    <span class="text-xs px-3 py-1.5 rounded-full {{ $badgeClass }}">
                                        {{ ucfirst((string) $pro->status) }}
                                    </span>
                                </td>

                                <td class="px-8 py-6 text-right">
                                    <div class="text-xs text-white/60 font-bold">{{ optional($pro->created_at)->format('Y-m-d') }}</div>
                                </td>

                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" data-pro='@json($payload)' onclick="openEditPro(this)"
                                            class="h-10 px-5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black text-xs uppercase tracking-widest transition-all active:scale-95">
                                            {{ __('Edit') }}
                                        </button>
                                        <button type="button" data-pro='@json($payload)' onclick="openTradeHistory(this)"
                                            class="h-10 px-5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black text-xs uppercase tracking-widest transition-all active:scale-95">
                                            {{ __('Add Trade History') }}
                                        </button>
                                        <form action="{{ route('admin.copy-trading.pros.delete') }}" method="POST" onsubmit="return confirm('{{ __('Delete this pro trader?') }}')">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $pro->id }}">
                                            <button
                                                class="h-10 px-5 rounded-2xl bg-red-500/15 border border-red-500/25 text-red-300 font-black text-xs uppercase tracking-widest hover:bg-red-500/20 transition-all active:scale-95">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-16 text-center text-text-secondary">
                                    {{ __('No pro traders yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        <div class="bg-secondary border border-white/5 rounded-[2rem] overflow-hidden shadow-2xl relative">
            <div class="bg-secondary/30 border-b border-white/5 p-4 lg:p-6 relative">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-white font-bold tracking-tight">{{ __('Recent Trade History') }}</h4>
                        <p class="text-[10px] text-text-secondary uppercase font-bold tracking-widest mt-0.5 opacity-50">
                            {{ __('Latest stored trade records for pro traders') }}</p>
                    </div>
                    <div class="text-xs text-text-secondary">
                        {{ __('Shows the most recent market results and pending limit orders.') }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/[0.02]">
                            <th class="px-6 py-4"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Trader') }}</span></th>
                            <th class="px-6 py-4"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Market') }}</span></th>
                            <th class="px-6 py-4"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Pair') }}</span></th>
                            <th class="px-6 py-4"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Type') }}</span></th>
                            <th class="px-6 py-4 text-right"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Entry') }}</span></th>
                            <th class="px-6 py-4 text-right"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('PnL') }}</span></th>
                            <th class="px-6 py-4 text-right"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Status') }}</span></th>
                            <th class="px-6 py-4 text-right"><span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Submitted') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse (($recentTrades ?? collect()) as $trade)
                            @php
                                $status = strtolower((string) ($trade['status'] ?? 'pending'));
                                $statusClass = match ($status) {
                                    'closed', 'filled' => 'bg-emerald-500/15 border border-emerald-500/25 text-emerald-400',
                                    'open', 'pending' => 'bg-amber-500/15 border border-amber-500/25 text-amber-300',
                                    default => 'bg-white/5 border border-white/10 text-white/60',
                                };
                                $pnl = $trade['pnl'];
                            @endphp
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-white">{{ $trade['trader_label'] }}</div>
                                    <div class="text-[11px] text-text-secondary mt-1">
                                        {{ strtoupper($trade['side']) }} · {{ $fmt8($trade['size']) }} · {{ $fmt2($trade['leverage']) }}x
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-white/80 uppercase">{{ $trade['market'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-white">{{ $trade['ticker'] }}</td>
                                <td class="px-6 py-4 text-sm text-white/80 uppercase">{{ $trade['order_type'] }}</td>
                                <td class="px-6 py-4 text-right text-sm text-white">{{ $fmt8($trade['entry_price']) }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($pnl === null)
                                        <span class="text-sm text-text-secondary">{{ __('Pending') }}</span>
                                    @else
                                        <span class="text-sm font-bold {{ $pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $pnl >= 0 ? '+' : '' }}{{ $fmt2($pnl) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[11px] px-3 py-1.5 rounded-full {{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-text-secondary">
                                    {{ optional($trade['created_at'])->format('Y-m-d H:i') ?? __('N/A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-text-secondary">
                                    {{ __('No trade history records found yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    <div id="minCopyAmountModal" class="modal">
        <div class="modal-content max-w-md">
            <div class="flex items-center justify-between gap-4">
                <div class="text-white font-black text-xl">{{ __('Copy Trading Settings') }}</div>
                <button type="button" onclick="closeModal('minCopyAmountModal')"
                    class="text-white/60 hover:text-white transition text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('admin.copy-trading.settings.min-amount') }}" method="POST" class="mt-6 grid grid-cols-1 gap-4">
                @csrf
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Minimum Copy Amount (USDT)') }}</label>
                    <input type="number" name="min_copy_amount" step="0.01" min="0"
                        value="{{ $fmt2($minCopyAmount) }}"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <button class="mt-2 h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95 transition-all">
                    {{ __('Update') }}
                </button>
            </form>
        </div>
    </div>

    <div id="createProModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between gap-4">
                <div class="text-white font-black text-xl">{{ __('Add Pro Trader') }}</div>
                <button type="button" onclick="closeModal('createProModal')"
                    class="text-white/60 hover:text-white transition text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.copy-trading.pros.store') }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="text-sm text-text-secondary">{{ __('User') }}</label>
                    <select name="user_id"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->username ?? $user->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Status') }}</label>
                    <select name="status"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Display Name') }}</label>
                    <input type="text" name="display_name"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Bio') }}</label>
                    <input type="text" name="bio"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Style') }}</label>
                    <input type="text" name="style"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="SWING">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Risk Level') }}</label>
                    <input type="text" name="risk_level"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="Conservative">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Profit Share (%)') }}</label>
                    <input type="number" name="profit_share_percent" step="0.01" min="0" max="100" value="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Min. Investment Amount') }}</label>
                    <input type="number" name="min_investment_amount" step="0.00000001" min="0" value="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Min. Investment Currency') }}</label>
                    <input type="text" name="min_investment_currency" value="USDT"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div class="md:col-span-2">
                    <button
                        class="mt-2 h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95 transition-all">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editProModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-white font-black text-xl">{{ __('Edit Pro Trader') }}</div>
                    <div class="text-xs text-text-secondary mt-1" id="editProUserLabel"></div>
                </div>
                <button type="button" onclick="closeModal('editProModal')"
                    class="text-white/60 hover:text-white transition text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.copy-trading.pros.update') }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="id" id="editProId">
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Display Name') }}</label>
                    <input type="text" name="display_name" id="editProDisplayName"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Bio') }}</label>
                    <input type="text" name="bio" id="editProBio"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Style') }}</label>
                    <input type="text" name="style" id="editProStyle"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="SWING">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Risk Level') }}</label>
                    <input type="text" name="risk_level" id="editProRiskLevel"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="Conservative">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Profit Share (%)') }}</label>
                    <input type="number" name="profit_share_percent" id="editProProfitShare" step="0.01" min="0" max="100"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Min. Investment Amount') }}</label>
                    <input type="number" name="min_investment_amount" id="editProMinInvestmentAmount" step="0.00000001" min="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Min. Investment Currency') }}</label>
                    <input type="text" name="min_investment_currency" id="editProMinInvestmentCurrency"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Status') }}</label>
                    <select name="status" id="editProStatus"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button
                        class="mt-2 h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95 transition-all">
                        {{ __('Update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="tradeHistoryModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-white font-black text-xl">{{ __('Add Trade History') }}</div>
                    <div class="text-xs text-text-secondary mt-1" id="tradeHistoryUserLabel"></div>
                </div>
                <button type="button" onclick="closeModal('tradeHistoryModal')"
                    class="text-white/60 hover:text-white transition text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('admin.copy-trading.trades.store') }}" method="POST"
                class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="pro_trader_id" id="tradeHistoryProId" value="{{ old('pro_trader_id') }}">

                @if ($errors->any() && old('pro_trader_id'))
                    <div class="md:col-span-2 rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3">
                        <div class="text-sm font-semibold text-red-200">{{ __('Please fix the trade history form errors.') }}</div>
                        <ul class="mt-2 space-y-1 text-xs text-red-200/90">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Market') }}</label>
                    <select name="market" id="tradeHistoryMarket"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="futures" @selected(old('market', 'futures') === 'futures')>{{ __('Futures') }}</option>
                        <option value="margin" @selected(old('market') === 'margin')>{{ __('Margin') }}</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Order Type') }}</label>
                    <select name="type" id="tradeHistoryType"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="market" @selected(old('type', 'market') === 'market')>{{ __('Market') }}</option>
                        <option value="limit" @selected(old('type') === 'limit')>{{ __('Limit') }}</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Ticker') }}</label>
                    <input type="text" name="ticker" id="tradeHistoryTicker"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="BTCUSDT" value="{{ old('ticker') }}" required>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Side') }}</label>
                    <select name="side" id="tradeHistorySide"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="buy" @selected(old('side', 'buy') === 'buy')>{{ __('Buy') }}</option>
                        <option value="sell" @selected(old('side') === 'sell')>{{ __('Sell') }}</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Amount (USDT)') }}</label>
                    <input type="number" name="amount" id="tradeHistoryAmount" step="0.01" min="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="0.00" value="{{ old('amount') }}" required>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Leverage') }}</label>
                    <input type="number" name="leverage" id="tradeHistoryLeverage" min="1" max="100"
                        value="{{ old('leverage', 10) }}"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        required>
                </div>

                <div id="tradeHistoryPriceWrap" class="hidden">
                    <label class="text-sm text-text-secondary">{{ __('Limit Price') }}</label>
                    <input type="number" name="price" id="tradeHistoryPrice" step="0.00000001" min="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="0.00" value="{{ old('price') }}">
                </div>

                <div id="tradeHistoryOrderModeWrap" class="hidden">
                    <label class="text-sm text-text-secondary">{{ __('Margin Order Mode') }}</label>
                    <select name="order_mode" id="tradeHistoryOrderMode"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none">
                        <option value="normal" @selected(old('order_mode', 'normal') === 'normal')>{{ __('Normal') }}</option>
                        <option value="borrow" @selected(old('order_mode') === 'borrow')>{{ __('Borrow') }}</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Take Profit (optional)') }}</label>
                    <input type="number" name="take_profit" id="tradeHistoryTakeProfit" step="0.00000001" min="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="0.00" value="{{ old('take_profit') }}">
                </div>

                <div>
                    <label class="text-sm text-text-secondary">{{ __('Stop Loss (optional)') }}</label>
                    <input type="number" name="stop_loss" id="tradeHistoryStopLoss" step="0.00000001" min="0"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="0.00" value="{{ old('stop_loss') }}">
                </div>

                <div id="tradeHistoryPnlWrap">
                    <label class="text-sm text-text-secondary">{{ __('PnL (required for completed trade)') }}</label>
                    <input type="number" name="pnl" id="tradeHistoryPnl" step="0.01"
                        class="mt-2 w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white/80 outline-none"
                        placeholder="0.00" value="{{ old('pnl') }}">
                    <p class="mt-1 text-xs text-text-secondary">Positive value = profit, Negative value = loss</p>
                </div>

                <div class="md:col-span-2">
                    <button
                        class="mt-2 h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95 transition-all">
                        {{ __('Submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal(id) {
            $('#' + id).show();
        }

        function closeModal(id) {
            $('#' + id).hide();
        }

        function openEditPro(btn) {
            const data = $(btn).data('pro');
            if (!data) return;

            $('#editProId').val(data.id || '');
            $('#editProUserLabel').text(data.user_label || '');
            $('#editProDisplayName').val(data.display_name || '');
            $('#editProBio').val(data.bio || '');
            $('#editProStyle').val(data.style || '');
            $('#editProRiskLevel').val(data.risk_level || '');
            $('#editProProfitShare').val((data.profit_share_percent ?? 0).toString());
            $('#editProMinInvestmentAmount').val((data.min_investment_amount ?? 0).toString());
            $('#editProMinInvestmentCurrency').val(data.min_investment_currency || 'USDT');
            $('#editProStatus').val(data.status || 'inactive');

            openModal('editProModal');
        }

        function syncTradeHistoryFields() {
            const market = ($('#tradeHistoryMarket').val() || 'futures').toString();
            const type = ($('#tradeHistoryType').val() || 'market').toString();

            if (type === 'limit') {
                $('#tradeHistoryPriceWrap').removeClass('hidden');
                $('#tradeHistoryPrice').attr('required', true);
                $('#tradeHistoryPnlWrap').addClass('hidden');
                $('#tradeHistoryPnl').attr('required', false);
            } else {
                $('#tradeHistoryPriceWrap').addClass('hidden');
                $('#tradeHistoryPrice').attr('required', false);
                $('#tradeHistoryPnlWrap').removeClass('hidden');
                $('#tradeHistoryPnl').attr('required', true);
            }

            if (market === 'margin') {
                $('#tradeHistoryOrderModeWrap').removeClass('hidden');
            } else {
                $('#tradeHistoryOrderModeWrap').addClass('hidden');
                $('#tradeHistoryOrderMode').val('normal');
            }
        }

        function openTradeHistory(btn) {
            const data = $(btn).data('pro');
            if (!data) return;

            $('#tradeHistoryProId').val(data.id || '');
            $('#tradeHistoryUserLabel').text(data.user_label || '');
            $('#tradeHistoryMarket').val('futures');
            $('#tradeHistoryType').val('market');
            $('#tradeHistoryTicker').val('');
            $('#tradeHistorySide').val('buy');
            $('#tradeHistoryAmount').val('');
            $('#tradeHistoryLeverage').val('10');
            $('#tradeHistoryPrice').val('');
            $('#tradeHistoryOrderMode').val('normal');
            $('#tradeHistoryTakeProfit').val('');
            $('#tradeHistoryStopLoss').val('');
            $('#tradeHistoryPnl').val('');
            syncTradeHistoryFields();
            openModal('tradeHistoryModal');
        }

        $(document).ready(function() {
            $('#proSearch').on('input', function() {
                const q = ($(this).val() || '').toString().trim().toLowerCase();
                $('#clearProSearch').toggleClass('hidden', !q);
                $('.pro-row').each(function() {
                    const hay = ($(this).data('search') || '').toString();
                    $(this).toggle(!q || hay.includes(q));
                });
            });

            $('#clearProSearch').on('click', function() {
                $('#proSearch').val('').trigger('input');
            });

            $('#tradeHistoryMarket, #tradeHistoryType').on('change', function() {
                syncTradeHistoryFields();
            });

            syncTradeHistoryFields();

            if (@json((bool) old('pro_trader_id'))) {
                openModal('tradeHistoryModal');
            }

            $(window).on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $(e.target).hide();
                }
            });
        });
    </script>
@endsection
