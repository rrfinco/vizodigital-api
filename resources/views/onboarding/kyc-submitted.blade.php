@extends('layouts.base')

@section('title', 'KYC submitted — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-md text-center">
            <div class="portal-card p-8 dark:border-slate-800">
                <h1 class="text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Documents received</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    Your KYC package is with our team. After approval you can sign in to the developer panel and use auto-issued UAT API keys.
                </p>
                <a href="{{ url('/user/login') }}" class="portal-btn-primary mt-8 inline-flex">Go to developer login</a>
            </div>
        </div>
    </div>
@endsection
