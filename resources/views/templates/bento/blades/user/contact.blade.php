@extends('templates.bento.blades.layouts.user')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-bold text-white font-heading">{{ __('Contact Support') }}</h1>
            <p class="text-text-secondary text-sm">
                {{ __('Have a question or need help? We\'re here to assist you.') }}
            </p>
        </div>

        <!-- Contact Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(getSetting('email'))
                <div class="bg-secondary-dark rounded-xl border border-white/10 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-accent-primary/20 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <path d="M22 6l-10 7L2 6"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">{{ __('Email') }}</h4>
                            <a href="mailto:{{ getSetting('email') }}" class="text-sm text-text-secondary hover:text-accent-primary transition-colors">
                                {{ getSetting('email') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
            @if(getSetting('phone'))
                <div class="bg-secondary-dark rounded-xl border border-white/10 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-accent-primary/20 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">{{ __('Phone') }}</h4>
                            <a href="tel:{{ getSetting('phone') }}" class="text-sm text-text-secondary hover:text-accent-primary transition-colors">
                                {{ getSetting('phone') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Contact Form -->
        <form id="contact-form" method="POST" action="{{ route('user.contact.send') }}"
            class="bg-secondary-dark rounded-xl border border-white/10 p-6 lg:p-8 relative overflow-hidden">
            @csrf
            <!-- Decorative Background -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-accent-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

            <div class="relative z-10 space-y-6">
                @if(session('success'))
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                        <p class="text-green-400 text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
                        <p class="text-red-400 text-sm font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="text-xs font-bold text-text-secondary uppercase tracking-wider">{{ __('Subject') }}</label>
                    <input type="text" name="subject" required
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-colors"
                        placeholder="{{ __('Enter subject') }}">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-text-secondary uppercase tracking-wider">{{ __('Message') }}</label>
                    <textarea name="message" rows="6" required
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-accent-primary focus:ring-1 focus:ring-accent-primary transition-colors resize-none"
                        placeholder="{{ __('Enter your message here...') }}"></textarea>
                </div>

                <button type="submit"
                    class="w-full md:w-auto px-8 py-3 bg-accent-primary hover:bg-accent-primary-hover text-white rounded-xl font-bold text-sm transition-colors cursor-pointer">
                    {{ __('Send Message') }}
                </button>
            </div>
        </form>
    </div>
@endsection
