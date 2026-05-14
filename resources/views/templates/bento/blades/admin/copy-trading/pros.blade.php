@extends('templates.bento.blades.admin.layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="bg-secondary border border-white/5 rounded-2xl p-5">
            <h2 class="text-white font-semibold text-lg">{{ __('Add / Update Pro Trader') }}</h2>

            <form action="{{ route('admin.copy-trading.pros.store') }}" method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="text-sm text-text-secondary">{{ __('User') }}</label>
                    <select name="user_id"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->username ?? $user->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Status') }}</label>
                    <select name="status"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Display Name') }}</label>
                    <input type="text" name="display_name"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div>
                    <label class="text-sm text-text-secondary">{{ __('Bio') }}</label>
                    <input type="text" name="bio"
                        class="mt-1 w-full bg-primary-dark border border-white/10 rounded-xl px-4 py-3 text-white/80"
                        placeholder="{{ __('Optional') }}">
                </div>
                <div class="md:col-span-2">
                    <button
                        class="bg-accent-primary text-white rounded-xl px-6 py-3 font-semibold hover:opacity-90 transition">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-secondary border border-white/5 rounded-2xl overflow-hidden">
            <div class="p-5 flex items-center justify-between">
                <h3 class="text-white font-semibold">{{ __('Pro Traders') }}</h3>
                <a href="{{ route('admin.copy-trading.relationships.index') }}"
                    class="text-sm text-accent-primary hover:underline">{{ __('View Relationships') }}</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-primary-dark/50 text-text-secondary">
                        <tr>
                            <th class="text-left px-5 py-3">{{ __('User') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Display Name') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Followers') }}</th>
                            <th class="text-left px-5 py-3">{{ __('Status') }}</th>
                            <th class="text-right px-5 py-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pros as $pro)
                            <tr class="border-t border-white/5">
                                <td class="px-5 py-4 text-white">
                                    {{ $pro->user->username ?? $pro->user->email }}
                                </td>
                                <td class="px-5 py-4 text-white/80">
                                    <form action="{{ route('admin.copy-trading.pros.update') }}" method="POST"
                                        class="flex flex-col gap-2">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $pro->id }}">
                                        <input type="text" name="display_name" value="{{ $pro->display_name }}"
                                            class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        <input type="text" name="bio" value="{{ $pro->bio }}"
                                            class="w-full bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                        <div class="flex items-center gap-2">
                                            <select name="status"
                                                class="bg-primary-dark border border-white/10 rounded-xl px-3 py-2 text-white/80">
                                                <option value="active" {{ $pro->status === 'active' ? 'selected' : '' }}>
                                                    {{ __('Active') }}</option>
                                                <option value="inactive"
                                                    {{ $pro->status === 'inactive' ? 'selected' : '' }}>
                                                    {{ __('Inactive') }}</option>
                                            </select>
                                            <button
                                                class="bg-accent-primary/20 border border-accent-primary/30 text-white rounded-xl px-4 py-2 font-semibold hover:bg-accent-primary/25 transition">
                                                {{ __('Update') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-5 py-4 text-white/80">
                                    {{ number_format($pro->followers_count ?? 0) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="text-xs px-2 py-1 rounded-full {{ $pro->status === 'active' ? 'bg-green-500/15 border border-green-500/25 text-green-400' : 'bg-white/5 border border-white/10 text-white/55' }}">
                                        {{ ucfirst($pro->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form action="{{ route('admin.copy-trading.pros.delete') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $pro->id }}">
                                        <button
                                            class="bg-red-500/15 border border-red-500/25 text-red-300 rounded-xl px-4 py-2 font-semibold hover:bg-red-500/20 transition">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-text-secondary">
                                    {{ __('No pro traders yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

