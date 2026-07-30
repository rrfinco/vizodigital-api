@extends('layouts.base')

@section('title', 'Check your email — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-md text-center">
            <div class="portal-card p-8 dark:border-slate-800">
                <h1 class="text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Check your email</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We sent a KYC upload link to your inbox. Complete document verification, then wait for admin approval before signing in.
                </p>
                <a href="{{ route('landing') }}" class="portal-btn-secondary mt-8 inline-flex">Back to home</a>
            </div>
        </div>
    </div>
@endsection
