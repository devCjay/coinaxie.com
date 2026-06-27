@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-4">
            <div>
                <a href="{{ route('admin.tickets.index') }}" class="text-sm text-accent-primary hover:underline">
                    {{ __('Back to Tickets') }}
                </a>
                <h1 class="text-2xl font-bold text-white mt-2">{{ $ticket->subject }}</h1>
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    <span class="text-sm text-text-secondary font-mono">{{ $ticket->ticket_number }}</span>
                    <span class="text-sm text-text-secondary">{{ $ticket->user->fullname ?: $ticket->user->username }}</span>
                    @if ($ticket->status === 'open')
                        <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase">
                            {{ __('Open') }}
                        </span>
                    @else
                        <span class="px-2 py-1 rounded-full bg-slate-500/10 text-slate-300 text-[10px] font-bold uppercase">
                            {{ __('Closed') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                @if ($ticket->status === 'open')
                    <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-red-500/10 text-red-400 font-bold hover:bg-red-500/20 transition-colors">
                            {{ __('Close Ticket') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.detail', $ticket->user->id) }}"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-white/5 text-white font-bold hover:bg-white/10 transition-colors">
                    {{ __('View User') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-secondary border border-white/5 rounded-2xl p-6 space-y-4">
                @foreach ($ticket->messages as $message)
                    <div class="flex {{ $message->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-3xl w-full lg:w-auto rounded-2xl border px-5 py-4 {{ $message->sender_type === 'admin' ? 'bg-accent-primary/10 border-accent-primary/20' : 'bg-white/5 border-white/10' }}">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div>
                                    <p class="text-sm font-bold text-white">
                                        {{ $message->sender_type === 'admin' ? ($message->admin->name ?? __('Admin')) : ($message->user->fullname ?: $message->user->username) }}
                                    </p>
                                    <p class="text-xs text-text-secondary">
                                        {{ $message->created_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $message->sender_type === 'admin' ? 'bg-accent-primary/10 text-accent-primary' : 'bg-emerald-500/10 text-emerald-400' }}">
                                    {{ __($message->sender_type) }}
                                </span>
                            </div>

                            <div class="text-sm text-text-secondary whitespace-pre-line leading-6">
                                {{ $message->message }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-6">
                <div class="bg-secondary border border-white/5 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-white">{{ __('Ticket Details') }}</h3>
                    <div class="space-y-3 mt-4 text-sm">
                        <div>
                            <p class="text-text-secondary">{{ __('User') }}</p>
                            <p class="text-white font-medium mt-1">{{ $ticket->user->fullname ?: $ticket->user->username }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">{{ __('Email') }}</p>
                            <p class="text-white font-medium mt-1">{{ $ticket->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">{{ __('Created') }}</p>
                            <p class="text-white font-medium mt-1">{{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-text-secondary">{{ __('Last Reply') }}</p>
                            <p class="text-white font-medium mt-1">
                                {{ optional($ticket->last_reply_at)->format('M d, Y h:i A') ?? __('Not available') }}
                            </p>
                        </div>
                        @if ($ticket->closed_at)
                            <div>
                                <p class="text-text-secondary">{{ __('Closed By') }}</p>
                                <p class="text-white font-medium mt-1">
                                    {{ $ticket->closedByAdmin->name ?? __('Admin') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-secondary border border-white/5 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-white">{{ __('Reply To User') }}</h3>

                    @if ($ticket->status === 'closed')
                        <p class="text-sm text-text-secondary mt-2">
                            {{ __('This ticket is closed. Replies are disabled.') }}
                        </p>
                    @else
                        <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <textarea name="message" rows="7"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-primary"
                                placeholder="{{ __('Type your reply...') }}">{{ old('message') }}</textarea>

                            <button type="submit"
                                class="w-full inline-flex items-center justify-center px-5 py-3 rounded-xl bg-accent-primary text-white font-bold hover:bg-accent-primary-hover transition-colors">
                                {{ __('Send Reply') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
