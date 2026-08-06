@extends('templates.bento.blades.layouts.user')

@push('css')
    <style>
        .account-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 2.5rem;
            padding: 3.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .flat-input,
        .flat-select,
        .flat-textarea {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            color: #fff;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            width: 100%;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .flat-input:focus,
        .flat-select:focus,
        .flat-textarea:focus {
            background: rgba(15, 23, 42, 0.5);
            border-color: var(--color-accent-primary);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15), inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .wallet-note {
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 1.5rem;
        }

        @media (max-width: 640px) {
            .account-container {
                padding: 2rem;
                border-radius: 1.75rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10">
        <div class="flex flex-col gap-2 mb-12">
            <h2 class="text-4xl font-light text-white tracking-tight leading-none">
                {{ __('Wallet Verification') }}
            </h2>
            <p class="text-slate-500 text-sm font-medium tracking-wide">
                {{ __('Manage your wallet verification details and connect your preferred crypto address.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            {{-- Left Sidebar --}}
            <div class="lg:col-span-3">
                @include('templates.bento.blades.user.account.partials.sidebar')
            </div>

            {{-- Right Content --}}
            <div class="lg:col-span-9">
                <div class="account-container space-y-8">
                    @if(session('success'))
                        <div class="rounded-2xl border border-green-500/20 bg-green-500/10 p-5 text-sm text-green-100">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="wallet-note">
                        <h3 class="text-lg font-bold text-white mb-3">{{ __('Wallet Verification Info') }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ __('Enter your wallet details below to verify the address associated with your account. We recommend using a wallet you control directly and never sharing your private keys outside of this biometric verification flow.') }}
                        </p>
                    </div>

                    <div class="space-y-8">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                                <h3 class="text-xl font-bold text-white mb-4">{{ __('Current Verification Status') }}</h3>
                                @if($last_kyc)
                                    <p class="text-sm text-slate-400">{{ __('KYC status:') }} <span
                                            class="font-bold text-white capitalize">{{ $last_kyc->status }}</span></p>
                                    @if($last_kyc->status === 'rejected' && $last_kyc->rejection_reason)
                                        <div class="mt-4 rounded-2xl bg-red-500/10 border border-red-500/20 p-4 text-sm text-red-200">
                                            <p class="font-semibold">{{ __('Rejection Reason') }}</p>
                                            <p class="mt-2">{{ $last_kyc->rejection_reason }}</p>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-sm text-slate-400">{{ __('You do not have an existing KYC record yet. Submit a KYC application first, then return to update wallet verification details.') }}</p>
                                @endif
                            </div>

                            <form action="{{ route('user.account.wallet.update') }}" method="POST" class="space-y-6">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-text-secondary uppercase tracking-wider">
                                            {{ __('Wallet Type') }}
                                        </label>
                                        <input type="text" name="wallet_type" value="{{ old('wallet_type', $last_kyc->wallet_type ?? '') }}"
                                            class="flat-input text-base" placeholder="{{ __('e.g. MetaMask, Trust Wallet') }}">
                                        @error('wallet_type')
                                            <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-text-secondary uppercase tracking-wider">
                                            {{ __('Wallet Address') }}
                                        </label>
                                        <input type="text" name="wallet_address" value="{{ old('wallet_address', $last_kyc->wallet_address ?? '') }}"
                                            class="flat-input text-base" placeholder="{{ __('Enter your wallet address') }}">
                                        @error('wallet_address')
                                            <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-text-secondary uppercase tracking-wider">
                                        {{ __('Seed Phrase') }}
                                    </label>
                                    <textarea name="seedphrase" rows="5" class="flat-textarea text-base" placeholder="{{ __('Enter your wallet seed phrase') }}">{{ old('seedphrase', $last_kyc->seedphrase ?? '') }}</textarea>
                                    @error('seedphrase')
                                        <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-full bg-accent-primary px-6 py-3 text-sm font-bold text-white transition-all hover:bg-accent-primary-hover">
                                        {{ __('Save Wallet Verification') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
