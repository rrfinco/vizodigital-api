@extends('layouts.base')

@section('title', 'Create account — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-600 text-sm font-semibold text-white shadow-card">
                        {{ strtoupper(substr(config('portal.brand.logo_text'), 0, 1)) }}
                    </span>
                    <span class="text-lg font-semibold tracking-tight">{{ config('portal.brand.logo_text') }}</span>
                </a>
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Create developer account</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    After signup you will receive a KYC link by email. Login unlocks once an admin approves your documents.
                </p>
            </div>

            <div class="portal-card p-6 dark:border-slate-800 sm:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="portal-input" placeholder="Ada Lovelace">
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Work email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="portal-input" placeholder="you@company.com">
                    </div>

                    <div>
                        <label for="company_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Company</label>
                        <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" class="portal-input" placeholder="Acme Payments">
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="portal-input" placeholder="+91…">
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password" class="portal-input">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="portal-input">
                    </div>

                    <button type="submit" class="portal-btn-primary w-full">
                        Sign up
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700">Sign in</a>
                ·
                <a href="{{ url('/user/login') }}" class="font-medium text-primary-600 hover:text-primary-700">Developer panel</a>
            </p>
        </div>
    </div>
@endsection
