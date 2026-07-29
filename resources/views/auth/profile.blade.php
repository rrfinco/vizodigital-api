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
    </div>
@endsection
