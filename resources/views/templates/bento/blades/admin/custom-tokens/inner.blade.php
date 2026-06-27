@php
    $fmt8 = fn($v) => rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.');
@endphp

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
                    $editPayload = [
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
                    ];
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
                                onclick='openEditToken(@json($editPayload))'>
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
