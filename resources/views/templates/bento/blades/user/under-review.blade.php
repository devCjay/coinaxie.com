@extends('templates.bento.blades.layouts.user')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-secondary-dark rounded-2xl border border-white/10 p-8 md:p-10 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>

            <h1 class="mt-6 text-2xl font-bold text-white">{{ __('Account Under Review') }}</h1>
            <p class="mt-3 text-text-secondary leading-relaxed">
                {{ __('Your account is currently being reviewed. You will be able to access your dashboard once your account is approved by our team.') }}
            </p>

            <div class="mt-8 rounded-xl border border-white/10 bg-white/5 p-5 text-left">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">{{ __('What happens next?') }}</h2>
                <ul class="mt-3 space-y-2 text-sm text-white/80">
                    <li>• {{ __('We are verifying your registration and KYC details.') }}</li>
                    <li>• {{ __('You will receive a notification once your account is approved.') }}</li>
                    <li>• {{ __('Please wait while our team completes the review.') }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
