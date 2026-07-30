@extends('layouts.docs')

@section('title', 'Payment Status — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-md text-center py-12">
        @if ($deposit && $deposit->status === 'success')
            {{-- Success State --}}
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-800 dark:text-white">Payment Successful</h1>
            <p class="mt-2 text-sm text-slate-500">
                ₹{{ number_format($deposit->amount, 2) }} has been credited to your wallet balance.
            </p>
            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-left text-xs text-slate-600 dark:bg-slate-900 dark:text-slate-400 font-mono space-y-1">
                <div>Order ID: {{ $deposit->order_id }}</div>
                @if($deposit->gateway_ref)
                    <div>Transaction ID: {{ $deposit->gateway_ref }}</div>
                @endif
            </div>
            <div class="mt-8">
                <a href="{{ route('profile') }}" class="inline-flex rounded-2xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition">
                    Go to Profile
                </a>
            </div>
        @elseif ($deposit && $deposit->status === 'failed')
            {{-- Failed State --}}
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-800 dark:text-white">Payment Failed</h1>
            <p class="mt-2 text-sm text-slate-500">
                We were unable to process your payment transaction.
            </p>
            @if ($orderId)
                <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-left text-xs text-slate-600 dark:bg-slate-900 dark:text-slate-400 font-mono">
                    Order ID: {{ $orderId }}
                </div>
            @endif
            <div class="mt-8">
                <a href="{{ route('profile') }}" class="inline-flex rounded-2xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition">
                    Try Again
                </a>
            </div>
        @else
            {{-- Pending/Processing State --}}
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </div>
            <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-800 dark:text-white">Payment Pending</h1>
            <p class="mt-2 text-sm text-slate-500">
                We are waiting for payment confirmation from the gateway. Your wallet balance will update automatically once complete.
            </p>
            @if ($orderId)
                <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-left text-xs text-slate-600 dark:bg-slate-900 dark:text-slate-400 font-mono">
                    Order ID: {{ $orderId }}
                </div>
            @endif
            <div class="mt-8">
                <a href="{{ route('profile') }}" class="inline-flex rounded-2xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition">
                    Go to Profile
                </a>
            </div>
        @endif
    </div>
@endsection
