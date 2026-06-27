@extends('templates.bento.blades.layouts.user')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <a href="{{ route('user.tickets.index') }}" class="text-sm text-accent-primary hover:underline">
                    {{ __('Back to Tickets') }}
                </a>
                <h1 class="text-2xl font-bold text-white mt-2">{{ $ticket->subject }}</h1>
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    <span class="text-sm text-text-secondary font-mono">{{ $ticket->ticket_number }}</span>
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

            <div class="text-sm text-text-secondary">
                <p>{{ __('Created') }}: {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                @if ($ticket->closed_at)
                    <p class="mt-1">{{ __('Closed') }}: {{ $ticket->closed_at->format('M d, Y h:i A') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-secondary border border-white/5 rounded-2xl p-6 space-y-4">
            @foreach ($ticket->messages as $message)
                <div class="flex {{ $message->sender_type === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div
                        class="max-w-3xl w-full lg:w-auto rounded-2xl border px-5 py-4 {{ $message->sender_type === 'user' ? 'bg-accent-primary/10 border-accent-primary/20' : 'bg-white/5 border-white/10' }}">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <div>
                                <p class="text-sm font-bold text-white">
                                    {{ $message->sender_type === 'user' ? __('You') : ($message->admin->name ?? __('Support')) }}
                                </p>
                                <p class="text-xs text-text-secondary">
                                    {{ $message->created_at->format('M d, Y h:i A') }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $message->sender_type === 'user' ? 'bg-accent-primary/10 text-accent-primary' : 'bg-emerald-500/10 text-emerald-400' }}">
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

        <div class="bg-secondary border border-white/5 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-white">{{ __('Reply To Ticket') }}</h3>

            @if ($ticket->status === 'closed')
                <p class="text-sm text-text-secondary mt-2">
                    {{ __('This ticket has been closed and can no longer receive replies.') }}
                </p>
            @else
                <form action="{{ route('user.tickets.reply', $ticket->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <textarea name="message" rows="6"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-primary"
                        placeholder="{{ __('Type your reply...') }}">{{ old('message') }}</textarea>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-accent-primary text-white font-bold hover:bg-accent-primary-hover transition-colors">
                        {{ __('Send Reply') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
