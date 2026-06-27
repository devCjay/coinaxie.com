@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-secondary border border-white/5 rounded-2xl p-6">
                <p class="text-text-secondary text-sm">{{ __('Total Tickets') }}</p>
                <h2 class="text-3xl font-bold text-white mt-2">{{ number_format($stats['total']) }}</h2>
            </div>
            <div class="bg-secondary border border-white/5 rounded-2xl p-6">
                <p class="text-text-secondary text-sm">{{ __('Open Tickets') }}</p>
                <h2 class="text-3xl font-bold text-emerald-400 mt-2">{{ number_format($stats['open']) }}</h2>
            </div>
            <div class="bg-secondary border border-white/5 rounded-2xl p-6">
                <p class="text-text-secondary text-sm">{{ __('Closed Tickets') }}</p>
                <h2 class="text-3xl font-bold text-slate-300 mt-2">{{ number_format($stats['closed']) }}</h2>
            </div>
        </div>

        <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-white">{{ __('Ticket Queue') }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ __('Review ticket conversations, reply to users, and close resolved issues.') }}
                    </p>
                </div>

                <form action="{{ route('admin.tickets.index') }}" method="GET" class="flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search ticket or user') }}"
                        class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-primary">
                    <select name="status"
                        class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-1 focus:ring-accent-primary">
                        <option value="all">{{ __('All Status') }}</option>
                        <option value="open" @selected(request('status') === 'open')>{{ __('Open') }}</option>
                        <option value="closed" @selected(request('status') === 'closed')>{{ __('Closed') }}</option>
                    </select>
                    <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-white/5 text-white hover:bg-white/10 transition-colors">
                        {{ __('Filter') }}
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-white/[0.03] text-left">
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('Ticket') }}</th>
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('User') }}</th>
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('Status') }}</th>
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('Messages') }}</th>
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('Last Reply') }}</th>
                            <th class="px-6 py-4 text-xs uppercase tracking-wider text-text-secondary">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($tickets as $ticket)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-white font-semibold">{{ $ticket->subject }}</div>
                                    <div class="text-xs text-text-secondary font-mono mt-1">{{ $ticket->ticket_number }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white">{{ $ticket->user->fullname ?: $ticket->user->username }}</div>
                                    <div class="text-xs text-text-secondary mt-1">{{ $ticket->user->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($ticket->status === 'open')
                                        <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase">
                                            {{ __('Open') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-slate-500/10 text-slate-300 text-[10px] font-bold uppercase">
                                            {{ __('Closed') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-text-secondary">{{ number_format($ticket->messages_count) }}</td>
                                <td class="px-6 py-4 text-text-secondary">
                                    {{ optional($ticket->last_reply_at)->diffForHumans() ?? __('Never') }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.tickets.show', $ticket->id) }}"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-accent-primary/10 text-accent-primary hover:bg-accent-primary/20 transition-colors">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center">
                                    <p class="text-white font-semibold">{{ __('No support tickets found.') }}</p>
                                    <p class="text-text-secondary text-sm mt-2">
                                        {{ __('New user tickets will appear here once they are created.') }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="p-6 border-t border-white/5">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
