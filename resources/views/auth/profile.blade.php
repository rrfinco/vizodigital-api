@extends('layouts.docs')

@section('title', 'Profile — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Profile</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">Profile</h1>
        <p class="mt-2 text-sm text-slate-500">Session account and assigned roles</p>

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($user->hasRole('super_admin'))
                <a href="/admin" class="inline-flex rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                    Go to Admin Panel &rarr;
                </a>
            @endif
            <a href="/user" class="inline-flex rounded-2xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 transition">
                Go to Developer Panel &rarr;
            </a>
        </div>

        <div class="portal-card mt-8 space-y-6 p-6 dark:border-slate-800">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</p>
                <p class="mt-1 text-sm font-medium">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</p>
                <p class="mt-1 text-sm font-medium">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Roles</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($roles as $role)
                        <span class="rounded-2xl bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                            {{ $role }}
                        </span>
                    @empty
                        <span class="text-sm text-slate-400">No roles assigned</span>
                    @endforelse
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Permissions</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    @forelse ($permissions as $permission)
                        <li class="font-mono text-xs">{{ $permission }}</li>
                    @empty
                        <li class="text-slate-400">No direct permissions</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white mt-12">Wallet & Add Funds</h1>
        <p class="mt-2 text-sm text-slate-500">Manage your main balance and lifetime commissions, or add funds directly to your wallet.</p>

        <div class="grid grid-cols-2 gap-4 mt-6">
            <div class="portal-card p-6 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Main Wallet Balance</p>
                <p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">₹{{ number_format($user->wallet_balance, 2) }}</p>
            </div>
            <div class="portal-card p-6 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Earning Wallet (Commission)</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($user->earning_balance, 2) }}</p>
            </div>
        </div>

        <div class="portal-card mt-6 p-6 dark:border-slate-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-4">Add Funds to Wallet</p>
            <form method="POST" action="{{ route('payment.initiate') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="amount" class="sr-only">Amount</label>
                    <div class="relative rounded-2xl shadow-sm">
                         <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                             <span class="text-slate-500 sm:text-sm">₹</span>
                         </div>
                        <input
                            type="number"
                            name="amount"
                            id="amount"
                            min="1"
                            step="1"
                            class="block w-full rounded-2xl border-portal-border pl-8 pr-12 py-3 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                            placeholder="0.00"
                            required
                        >
                    </div>
                    @error('amount')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    class="w-full rounded-2xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition"
                >
                    Proceed to Payment
                </button>
            </form>
        </div>
    </div>
@endsection
