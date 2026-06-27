@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
            <div class="p-5 flex items-center justify-between">
                <h3 class="text-white font-semibold">{{ __('Copy Relationships') }}</h3>
                <a href="{{ route('admin.copy-trading.pros.index') }}"
                    class="text-sm text-accent-primary hover:underline">{{ __('Manage Pro Traders') }}</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-dark/50 text-text-secondary">
                        <tr>
                            <th class="text-left px-5 py-3">{{ __('Follower') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Pro Trader') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Market') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Allocation') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Max Leverage') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Status') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($relationships as $rel)
                            <tr class="border-t border-white/5">
                                <td class="px-5 py-4 text-white">
                                    {{ $rel->follower->username ?? $rel->follower->email }}
                                </td>
                                <td class="px-5 py-4 text-white/80">
                                    {{ $rel->proTrader->display_name ?: ($rel->proTrader->user->username ?? $rel->proTrader->user->email) }}
                                </td>
                                <td class="px-5 py-4 text-white/80">{{ ucfirst($rel->market_type) }}</td>
                                <td class="px-5 py-4 text-white/80">
                                    {{ ucfirst($rel->allocation_type) }}:
                                    {{ rtrim(rtrim(number_format($rel->allocation_value, 4, '.', ''), '0'), '.') }}
                                    {{ $rel->allocation_type === 'percent' ? '%' : '' }}
                                </td>
                                <td class="px-5 py-4 text-white/80">{{ (int) $rel->max_leverage }}x</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="text-xs px-2 py-1 rounded-full {{ $rel->status === 'active' ? 'bg-green-500/15 border border-green-500/25 text-green-400' : 'bg-white/5 border border-white/10 text-white/55' }}">
                                        {{ ucfirst($rel->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-white/55">{{ $rel->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-text-secondary">
                                    {{ __('No relationships yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-5">
                {{ $relationships->links() }}
            </div>
        </div>
    </div>
@endsection

