@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    @php
        $fmt8 = fn($v) => rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.');
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
                                d="M3 3v18h18M7 14l3-3 4 4 7-7"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Total Tokens') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['active'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Active') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-accent-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-accent-primary/10 flex items-center justify-center text-accent-primary mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 17V7a3 3 0 0 1 3-3h10"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 9h10a3 3 0 0 1 3 3v8"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['futures'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Futures') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-violet-500/10 flex items-center justify-center text-violet-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.5 13.5l3-3"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.5 15.5l-1 1a4 4 0 0 1-5.7-5.7l1-1"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.5 8.5l1-1a4 4 0 0 1 5.7 5.7l-1 1"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['margin'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Margin') }}</div>
                </div>
            </div>

            <div class="bg-secondary border border-white/5 rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-white leading-none">{{ number_format((int) ($stats['both'] ?? 0)) }}</div>
                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest mt-2 opacity-60">
                        {{ __('Both') }}</div>
                </div>
            </div>
        </div>

        <div class="bg-secondary border border-white/5 rounded-[2rem] overflow-hidden shadow-2xl relative">
            <div class="bg-secondary/30 border-b border-white/5 p-4 lg:p-6 relative">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-text-secondary border border-white/10 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3v18h18M7 14l3-3 4 4 7-7"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold tracking-tight">{{ __('Custom Tokens') }}</h4>
                            <p class="text-[10px] text-text-secondary uppercase font-bold tracking-widest mt-0.5 opacity-50">
                                {{ __('Admin-managed market prices for Futures & Margin') }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.custom-tokens.index') }}" class="flex-1 max-w-2xl flex flex-wrap gap-3 lg:justify-end">
                        <div class="relative flex-1 group min-w-[200px]">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ __('Search by symbol...') }}"
                                class="w-full h-12 bg-white/5 border border-white/10 rounded-2xl px-5 text-sm font-medium text-white focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-all outline-none placeholder:text-text-secondary/30">
                            <button type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 hover:bg-white/10 rounded-xl text-text-secondary transition-colors group-hover:text-accent-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>

                        <select name="market"
                            class="h-12 bg-white/5 border border-white/10 rounded-2xl px-5 text-sm font-bold text-white focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-all outline-none appearance-none min-w-[160px]">
                            @php $m = (string) request('market', 'all'); @endphp
                            <option value="all" class="bg-secondary-dark" {{ $m === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                            <option value="both" class="bg-secondary-dark" {{ $m === 'both' ? 'selected' : '' }}>{{ __('Both') }}</option>
                            <option value="futures" class="bg-secondary-dark" {{ $m === 'futures' ? 'selected' : '' }}>{{ __('Futures') }}</option>
                            <option value="margin" class="bg-secondary-dark" {{ $m === 'margin' ? 'selected' : '' }}>{{ __('Margin') }}</option>
                        </select>

                        @if (request('search') || request('market'))
                            <a href="{{ route('admin.custom-tokens.index') }}"
                                class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-text-secondary hover:text-white flex items-center gap-2 transition-all font-bold text-xs uppercase tracking-widest shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                {{ __('Clear') }}
                            </a>
                        @endif

                        <button type="button" onclick="openModal('createTokenModal')"
                            class="h-12 px-6 rounded-2xl bg-accent-primary text-white flex items-center gap-2 transition-all font-black text-xs uppercase tracking-widest shadow-lg hover:opacity-90 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ __('Add Token') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/[0.02]">
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Symbol') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Market') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Price') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('24h %') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('High / Low') }}</span>
                            </th>
                            <th class="px-8 py-6 text-left">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Volume') }}</span>
                            </th>
                            <th class="px-8 py-6 text-right">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-text-secondary opacity-60">{{ __('Actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($tokens as $t)
                            @php
                                $pct = (float) $t->change_1d_percentage;
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-6">
                                    <div class="text-white font-black">{{ $t->ticker }}</div>
                                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60">
                                        {{ $t->is_active ? __('Active') : __('Disabled') }}
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-white font-bold uppercase text-xs tracking-widest">{{ $t->market }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-white font-black">{{ $fmt8($t->current_price) }}</div>
                                    <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60">
                                        {{ __('Open') }}: {{ $fmt8($t->open_price ?? $t->current_price) }}
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="inline-flex items-center px-3 py-1 rounded-2xl border text-xs font-black tracking-widest {{ $pct >= 0 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border-red-500/20 text-red-400' }}">
                                        {{ $pct >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.') }}%
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-white/90 font-bold text-sm">
                                        {{ $fmt8($t->high ?? $t->current_price) }} / {{ $fmt8($t->low ?? $t->current_price) }}
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-white/90 font-bold text-sm">{{ $fmt8($t->volume ?? 0) }}</div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                            class="h-10 px-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black text-xs uppercase tracking-widest"
                                            onclick="openEditToken(@json([
                                                'id' => (int) $t->id,
                                                'market' => (string) $t->market,
                                                'ticker' => (string) $t->ticker,
                                                'current_price' => (float) $t->current_price,
                                                'change_1d_percentage' => (float) $t->change_1d_percentage,
                                                'open_price' => $t->open_price !== null ? (float) $t->open_price : null,
                                                'high' => $t->high !== null ? (float) $t->high : null,
                                                'low' => $t->low !== null ? (float) $t->low : null,
                                                'volume' => $t->volume !== null ? (float) $t->volume : null,
                                                'is_active' => (bool) $t->is_active,
                                            ])">
                                            {{ __('Edit') }}
                                        </button>
                                        <form method="POST" action="{{ route('admin.custom-tokens.delete') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $t->id }}">
                                            <button type="submit"
                                                class="h-10 px-4 rounded-2xl bg-red-500/10 hover:bg-red-500/15 border border-red-500/20 text-red-300 font-black text-xs uppercase tracking-widest">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-8 py-16">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-text-secondary">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 3v18h18M7 14l3-3 4 4 7-7"></path>
                                            </svg>
                                        </div>
                                        <div class="text-white font-black">{{ __('No tokens found') }}</div>
                                        <div class="text-[10px] text-text-secondary font-bold uppercase tracking-widest opacity-60">
                                            {{ __('Create a custom token to override market prices.') }}
                                        </div>
                                        <button type="button" onclick="openModal('createTokenModal')"
                                            class="mt-2 h-12 px-6 rounded-2xl bg-accent-primary text-white flex items-center gap-2 transition-all font-black text-xs uppercase tracking-widest shadow-lg hover:opacity-90 active:scale-95">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            {{ __('Add Token') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tokens->hasPages())
                <div class="bg-secondary/30 border-t border-white/5 p-6">
                    <div class="flex justify-center">
                        {{ $tokens->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div id="createTokenModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <div class="text-white font-black text-lg">{{ __('Add Token') }}</div>
                <button type="button" onclick="closeModal('createTokenModal')" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black">×</button>
            </div>

            <form method="POST" action="{{ route('admin.custom-tokens.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Market') }}</label>
                    <select name="market"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-sm font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none appearance-none">
                        <option value="both" class="bg-secondary-dark">{{ __('Both') }}</option>
                        <option value="futures" class="bg-secondary-dark">{{ __('Futures') }}</option>
                        <option value="margin" class="bg-secondary-dark">{{ __('Margin') }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Symbol') }}</label>
                    <input type="text" name="ticker" placeholder="BTCUSDT" required
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Current Price') }}</label>
                    <input type="number" min="0" step="0.00000001" name="current_price" placeholder="0.00" required
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h Change (%)') }}</label>
                    <input type="number" step="0.01" name="change_1d_percentage" placeholder="0"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Open') }}</label>
                    <input type="number" min="0" step="0.00000001" name="open_price" placeholder="0.00"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h High') }}</label>
                    <input type="number" min="0" step="0.00000001" name="high" placeholder="0.00"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h Low') }}</label>
                    <input type="number" min="0" step="0.00000001" name="low" placeholder="0.00"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Volume') }}</label>
                    <input type="number" min="0" step="0.00000001" name="volume" placeholder="0"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex items-center gap-3 md:col-span-2">
                    <input id="create-is-active" type="checkbox" name="is_active" value="1" checked
                        class="w-5 h-5 rounded bg-white/5 border border-white/10 text-accent-primary focus:ring-accent-primary/20">
                    <label for="create-is-active" class="text-sm text-white font-bold">{{ __('Active') }}</label>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('createTokenModal')"
                        class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black text-xs uppercase tracking-widest">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editTokenModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <div class="text-white font-black text-lg">{{ __('Edit Token') }}</div>
                <button type="button" onclick="closeModal('editTokenModal')" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black">×</button>
            </div>

            <form method="POST" action="{{ route('admin.custom-tokens.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                <input type="hidden" name="id" id="edit-id">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Market') }}</label>
                    <select name="market" id="edit-market"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-sm font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none appearance-none">
                        <option value="both" class="bg-secondary-dark">{{ __('Both') }}</option>
                        <option value="futures" class="bg-secondary-dark">{{ __('Futures') }}</option>
                        <option value="margin" class="bg-secondary-dark">{{ __('Margin') }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Symbol') }}</label>
                    <input type="text" name="ticker" id="edit-ticker" required
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Current Price') }}</label>
                    <input type="number" min="0" step="0.00000001" name="current_price" id="edit-current-price" required
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h Change (%)') }}</label>
                    <input type="number" step="0.01" name="change_1d_percentage" id="edit-change"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Open') }}</label>
                    <input type="number" min="0" step="0.00000001" name="open_price" id="edit-open"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h High') }}</label>
                    <input type="number" min="0" step="0.00000001" name="high" id="edit-high"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('24h Low') }}</label>
                    <input type="number" min="0" step="0.00000001" name="low" id="edit-low"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Volume') }}</label>
                    <input type="number" min="0" step="0.00000001" name="volume" id="edit-volume"
                        class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-base font-bold focus:border-accent-primary/50 focus:ring-4 focus:ring-accent-primary/10 transition-all outline-none">
                </div>
                <div class="flex items-center gap-3 md:col-span-2">
                    <input id="edit-is-active" type="checkbox" name="is_active" value="1"
                        class="w-5 h-5 rounded bg-white/5 border border-white/10 text-accent-primary focus:ring-accent-primary/20">
                    <label for="edit-is-active" class="text-sm text-white font-bold">{{ __('Active') }}</label>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('editTokenModal')"
                        class="h-12 px-6 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black text-xs uppercase tracking-widest">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="h-12 px-6 rounded-2xl bg-accent-primary text-white font-black text-xs uppercase tracking-widest hover:opacity-90 active:scale-95">
                        {{ __('Update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'block';
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        }

        function openEditToken(data) {
            document.getElementById('edit-id').value = data.id || '';
            document.getElementById('edit-market').value = data.market || 'both';
            document.getElementById('edit-ticker').value = data.ticker || '';
            document.getElementById('edit-current-price').value = data.current_price ?? '';
            document.getElementById('edit-change').value = data.change_1d_percentage ?? 0;
            document.getElementById('edit-open').value = data.open_price ?? '';
            document.getElementById('edit-high').value = data.high ?? '';
            document.getElementById('edit-low').value = data.low ?? '';
            document.getElementById('edit-volume').value = data.volume ?? '';
            document.getElementById('edit-is-active').checked = !!data.is_active;
            openModal('editTokenModal');
        }

        window.addEventListener('click', function(event) {
            const createModal = document.getElementById('createTokenModal');
            const editModal = document.getElementById('editTokenModal');
            if (event.target === createModal) closeModal('createTokenModal');
            if (event.target === editModal) closeModal('editTokenModal');
        });
    </script>
@endsection

