@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="bg-secondary border border-white/5 rounded-2xl p-5">
            <h2 class="text-white font-semibold text-lg">{{ __('Create Launchpad Project') }}</h2>

            <form action="{{ route('admin.launchpad.store') }}" method="POST"
                class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Name') }}</label>
                    <input type="text" name="name" required
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="{{ __('Project name') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Token Symbol') }}</label>
                    <input type="text" name="token_symbol" required
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="ABC">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Quote Currency') }}</label>
                    <input type="text" name="quote_currency" value="USDT" required
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="USDT">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Sale Price') }}</label>
                    <input type="number" step="0.00000001" min="0" name="sale_price" required
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="0.01">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Hard Cap (Quote)') }}</label>
                    <input type="number" step="0.00000001" min="0" name="hard_cap_quote"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="0">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Min Buy (Quote)') }}</label>
                    <input type="number" step="0.00000001" min="0" name="min_buy_quote"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="0">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Max Buy (Quote)') }}</label>
                    <input type="number" step="0.00000001" min="0" name="max_buy_quote"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="0">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Token Name') }}</label>
                    <input type="text" name="token_name"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Token Decimals') }}</label>
                    <input type="number" min="0" max="18" name="token_decimals" value="8"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="8">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Token Logo URL') }}</label>
                    <input type="text" name="token_logo_url"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="https://...">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm text-text-secondary">{{ __('Description') }}</label>
                    <textarea name="description" rows="3"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="{{ __('Optional') }}"></textarea>
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Sale Start At') }}</label>
                    <input type="datetime-local" name="sale_start_at"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Sale End At') }}</label>
                    <input type="datetime-local" name="sale_end_at"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Launch At') }}</label>
                    <input type="datetime-local" name="launch_at"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80">
                </div>
                <div class="md:col-span-2">
                    <button
                        class="bg-accent-primary text-white rounded-xl px-6 py-3 font-semibold hover:opacity-90 transition">
                        {{ __('Create') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
            <div class="p-5 flex items-center justify-between">
                <h3 class="text-white font-semibold">{{ __('Launchpad Projects') }}</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-dark/50 text-text-secondary">
                        <tr>
                            <th class="text-left px-5 py-3">{{ __('Project') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Sale') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Status') }}</th>
                            <th class="text-right px-5 py-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $p)
                            <tr class="border-t border-white/5 align-top">
                                <td class="px-5 py-4 text-white">
                                    <div class="font-semibold">{{ $p->name }}</div>
                                    <div class="text-white/55 text-xs mt-1">{{ strtoupper($p->token_symbol) }}/{{ strtoupper($p->quote_currency) }}</div>
                                    <div class="text-white/55 text-xs mt-1">
                                        {{ __('Sold') }}:
                                        {{ rtrim(rtrim(number_format((float) $p->sold_quote, 8, '.', ''), '0'), '.') }}
                                        {{ strtoupper($p->quote_currency) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-white/80">
                                    <form action="{{ route('admin.launchpad.update') }}" method="POST" class="grid grid-cols-1 gap-2">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <input type="text" name="name" value="{{ $p->name }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <input type="text" name="quote_currency" value="{{ strtoupper($p->quote_currency) }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <input type="number" step="0.00000001" min="0" name="sale_price"
                                                value="{{ (float) $p->sale_price }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <input type="number" step="0.00000001" min="0" name="hard_cap_quote"
                                                value="{{ (float) $p->hard_cap_quote }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <input type="number" step="0.00000001" min="0" name="min_buy_quote"
                                                value="{{ (float) $p->min_buy_quote }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <input type="number" step="0.00000001" min="0" name="max_buy_quote"
                                                value="{{ (float) $p->max_buy_quote }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <input type="text" name="token_name" value="{{ $p->token_name }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80"
                                                placeholder="{{ __('Token name') }}">
                                            <input type="number" min="0" max="18" name="token_decimals"
                                                value="{{ (int) ($p->token_decimals ?? 8) }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <select name="status"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                                <option value="draft" {{ $p->status === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                                <option value="live" {{ $p->status === 'live' ? 'selected' : '' }}>{{ __('Live') }}</option>
                                                <option value="ended" {{ $p->status === 'ended' ? 'selected' : '' }}>{{ __('Ended') }}</option>
                                                <option value="launched" {{ $p->status === 'launched' ? 'selected' : '' }}>{{ __('Launched') }}</option>
                                                <option value="canceled" {{ $p->status === 'canceled' ? 'selected' : '' }}>{{ __('Canceled') }}</option>
                                            </select>
                                        </div>
                                        <input type="text" name="token_logo_url" value="{{ $p->token_logo_url }}"
                                            class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80"
                                            placeholder="{{ __('Token logo url') }}">
                                        <textarea name="description" rows="2"
                                            class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80"
                                            placeholder="{{ __('Description') }}">{{ $p->description }}</textarea>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <input type="datetime-local" name="sale_start_at"
                                                value="{{ $p->sale_start_at ? $p->sale_start_at->format('Y-m-d\\TH:i') : '' }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <input type="datetime-local" name="sale_end_at"
                                                value="{{ $p->sale_end_at ? $p->sale_end_at->format('Y-m-d\\TH:i') : '' }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                            <input type="datetime-local" name="launch_at"
                                                value="{{ $p->launch_at ? $p->launch_at->format('Y-m-d\\TH:i') : '' }}"
                                                class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        </div>

                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button
                                                class="bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2 font-semibold hover:bg-accent-primary/25 transition">
                                                {{ __('Update') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-5 py-4">
                                    <div
                                        class="text-xs px-2 py-1 rounded-full inline-block {{ $p->status === 'live' ? 'bg-green-500/15 border border-green-500/25 text-green-400' : ($p->status === 'launched' ? 'bg-blue-500/15 border border-blue-500/25 text-blue-400' : 'bg-white/5 border border-white/10 text-white/55') }}">
                                        {{ ucfirst($p->status) }}
                                    </div>
                                    <div class="text-white/55 text-xs mt-2">
                                        {{ __('Trading') }}: {{ $p->trading_enabled ? __('Enabled') : __('Disabled') }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <form action="{{ route('admin.launchpad.finalize') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $p->id }}">
                                            <button
                                                class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2 font-semibold hover:bg-white/10 transition">
                                                {{ __('Finalize') }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.launchpad.enable-trading') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $p->id }}">
                                            <button
                                                class="bg-green-500/15 border border-green-500/25 text-green-300 rounded-xl px-4 py-2 font-semibold hover:bg-green-500/20 transition">
                                                {{ __('Enable Trading') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-text-secondary">
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
@endsection
