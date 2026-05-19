@extends('templates.bento.blades.layouts.user')

@section('content')
    <div class="min-h-screen px-2 md:px-0">
        <div class="mb-4 md:mb-6 bg-secondary border border-white/5 rounded-2xl p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-white text-lg md:text-xl font-semibold">{{ $page_title }}</h2>
                    <p class="text-white/55 text-sm">{{ __('Buy launchpad tokens before launch and trade after launch.') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
            @forelse ($projects as $project)
                @php
                    $badgeClass = match ($project->status) {
                        'live' => 'bg-green-500/15 border border-green-500/25 text-green-400',
                        'launched' => 'bg-blue-500/15 border border-blue-500/25 text-blue-400',
                        'ended' => 'bg-amber-500/15 border border-amber-500/25 text-amber-400',
                        'canceled' => 'bg-red-500/15 border border-red-500/25 text-red-300',
                        default => 'bg-white/5 border border-white/10 text-white/55',
                    };
                @endphp
                <div class="bg-secondary border border-white/5 rounded-2xl p-5 h-full flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            @if ($project->token_logo_url)
                                <img src="{{ $project->token_logo_url }}" alt="{{ $project->token_symbol }}"
                                    class="w-11 h-11 rounded-xl object-cover border border-white/10">
                            @else
                                <div
                                    class="w-11 h-11 rounded-xl bg-accent-primary/20 grid place-items-center text-accent-primary font-bold">
                                    {{ strtoupper(substr($project->token_symbol, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-white font-semibold">{{ $project->name }}</div>
                                <div class="text-white/55 text-xs">
                                    {{ strtoupper($project->token_symbol) }}/{{ strtoupper($project->quote_currency) }}
                                </div>
                            </div>
                        </div>
                        <div class="text-xs px-2 py-1 rounded-full {{ $badgeClass }}">
                            {{ ucfirst($project->status) }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3">
                            <div class="text-white/55 text-xs">{{ __('Sale Price') }}</div>
                            <div class="text-white font-semibold mt-0.5">
                                {{ rtrim(rtrim(number_format((float) $project->sale_price, 8, '.', ''), '0'), '.') }}
                                {{ strtoupper($project->quote_currency) }}
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3">
                            <div class="text-white/55 text-xs">{{ __('Sold') }}</div>
                            <div class="text-white font-semibold mt-0.5">
                                {{ rtrim(rtrim(number_format((float) $project->sold_quote, 8, '.', ''), '0'), '.') }}
                                {{ strtoupper($project->quote_currency) }}
                            </div>
                        </div>
                    </div>

                    @if ($project->description)
                        <div class="mt-4 text-white/70 text-sm leading-relaxed line-clamp-3">
                            {{ $project->description }}
                        </div>
                    @endif

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
                <div class="bg-secondary border border-white/5 rounded-2xl p-8 text-center text-white/50 md:col-span-2 lg:col-span-3">
                    {{ __('No launchpad projects yet.') }}
                </div>
            @endforelse
        </div>
    </div>
@endsection
