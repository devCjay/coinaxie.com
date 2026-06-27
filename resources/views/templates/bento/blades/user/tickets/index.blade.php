@extends('templates.bento.blades.layouts.user')

@section('content')
    <div class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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

        @php
            $shouldOpenCreateTicketModal = $errors->has('subject') || $errors->has('message') || $errors->has('attachment');
        @endphp

        <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-white">{{ __('Your Tickets') }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ __('Track responses, review previous conversations, and manage open issues.') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <form action="{{ route('user.tickets.index') }}" method="GET" class="flex flex-wrap gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="{{ __('Search subject or ticket number') }}"
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

                    <button type="button" data-open-ticket-modal
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-accent-primary text-white font-bold hover:bg-accent-primary-hover transition-colors">
                        {{ __('Create Ticket') }}
                    </button>
                </div>
            </div>

            <div class="divide-y divide-white/5">
                @forelse ($tickets as $ticket)
                    <a href="{{ route('user.tickets.show', $ticket->id) }}"
                        class="block p-5 hover:bg-white/[0.03] transition-colors">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-sm font-bold text-white">{{ $ticket->subject }}</span>
                                    <span class="text-xs text-text-secondary font-mono">{{ $ticket->ticket_number }}</span>
                                    @if ($ticket->status === 'open')
                                        <span
                                            class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase">
                                            {{ __('Open') }}
                                        </span>
                                    @else
                                        <span
                                            class="px-2 py-1 rounded-full bg-slate-500/10 text-slate-300 text-[10px] font-bold uppercase">
                                            {{ __('Closed') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-text-secondary mt-2">
                                    {{ __('Messages') }}: {{ number_format($ticket->messages_count) }}
                                </p>
                            </div>

                            <div class="text-sm text-text-secondary lg:text-right">
                                <p>{{ __('Updated') }}: {{ optional($ticket->last_reply_at)->diffForHumans() ?? __('Never') }}</p>
                                <p class="mt-1">{{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-white font-semibold">{{ __('No support tickets found.') }}</p>
                        <p class="text-text-secondary text-sm mt-2">
                            {{ __('Create your first ticket using the Create Ticket button to get help from support.') }}
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($tickets->hasPages())
                <div class="p-6 border-t border-white/5">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>

        <div id="create-ticket-modal" class="fixed inset-0 z-[110] hidden items-center justify-center p-4">
            <button type="button" data-close-ticket-modal
                class="absolute inset-0 bg-black/80 backdrop-blur-md cursor-pointer"></button>

            <div data-modal-panel class="relative w-full max-w-xl bg-secondary border border-white/5 rounded-2xl p-6">
                <div class="p-6 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ __('Open New Ticket') }}</h3>
                        <p class="text-sm text-text-secondary mt-1">
                            {{ __('Describe your issue and our support team will respond inside the ticket thread.') }}
                        </p>
                    </div>

                    <button type="button" data-close-ticket-modal
                        class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-white/5 text-text-secondary hover:bg-white/10 hover:text-white transition-colors"
                        aria-label="{{ __('Close') }}">
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                </div>

                <div class="pt-6">
                    @if ($shouldOpenCreateTicketModal)
                        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            {{ __('Please fix the highlighted ticket form errors and try again.') }}
                        </div>
                    @endif

                    <form action="{{ route('user.tickets.store') }}" method="POST" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-text-secondary mb-2">{{ __('Subject') }}</label>
                            <input type="text" name="subject" value="{{ old('subject') }}"
                                class="w-full bg-white/5 border {{ $errors->has('subject') ? 'border-red-500/40' : 'border-white/10' }} rounded-xl px-4 py-3 text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-primary"
                                placeholder="{{ __('Brief summary of your issue') }}">
                            @error('subject')
                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm text-text-secondary mb-2">{{ __('Message') }}</label>
                            <textarea name="message" rows="8"
                                class="w-full bg-white/5 border {{ $errors->has('message') ? 'border-red-500/40' : 'border-white/10' }} rounded-xl px-4 py-3 text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-primary"
                                placeholder="{{ __('Explain the issue in detail...') }}">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm text-text-secondary mb-2">{{ __('Image Attachment') }}</label>
                            <input type="file" name="attachment" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                class="w-full bg-white/5 border {{ $errors->has('attachment') ? 'border-red-500/40' : 'border-white/10' }} rounded-xl px-4 py-3 text-white file:mr-4 file:rounded-lg file:border-0 file:bg-accent-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-accent-primary-hover">
                            <p class="mt-2 text-xs text-text-secondary">
                                {{ __('Optional. Upload JPG, PNG, GIF, or WEBP up to 5MB.') }}
                            </p>
                            @error('attachment')
                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-accent-primary text-white font-bold hover:bg-accent-primary-hover transition-colors">
                            {{ __('Create Ticket') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('create-ticket-modal');

            if (!modal) {
                return;
            }

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-open-ticket-modal]').forEach((button) => {
                button.addEventListener('click', openModal);
            });

            modal.querySelectorAll('[data-close-ticket-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            if (@json($shouldOpenCreateTicketModal)) {
                openModal();
            }
        });
    </script>
@endpush
