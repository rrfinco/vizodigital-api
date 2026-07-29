@extends('layouts.base')

@section('title', 'KYC verification — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-xl">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">KYC document upload</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ $user->name }} · {{ $user->email }}
                </p>
                @if ($user->onboarding_status?->value === 'rejected' && $user->rejection_reason)
                    <div class="mt-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-left text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        Previous submission rejected: {{ $user->rejection_reason }}
                    </div>
                @endif
            </div>

            <div class="portal-card p-6 dark:border-slate-800 sm:p-8"
                 x-data="{ rows: [{ type: 'company_registration', id: 1 }], nextId: 2 }">
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('onboarding.kyc.store', ['token' => $token]) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="company_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Company</label>
                            <input id="company_name" type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="portal-input">
                        </div>
                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="portal-input">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Documents</p>
                            <button type="button" class="text-sm font-medium text-primary-600 hover:text-primary-700"
                                    @click="rows.push({ type: 'identity_proof', id: nextId++ })"
                                    x-show="rows.length < 10">
                                + Add another
                            </button>
                        </div>

                        <template x-for="(row, index) in rows" :key="row.id">
                            <div class="grid gap-3 rounded-2xl border border-portal-border p-4 dark:border-slate-800 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
                                    <select :name="`documents[${index}][type]`" class="portal-input" x-model="row.type" required>
                                        <option value="company_registration">Company registration</option>
                                        <option value="identity_proof">Identity proof</option>
                                        <option value="address_proof">Address proof</option>
                                        <option value="bank_proof">Bank proof</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">File (PDF/JPG/PNG, max 10MB)</label>
                                    <input type="file" :name="`documents[${index}][file]`" class="portal-input" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                </div>
                                <div class="sm:col-span-2" x-show="rows.length > 1">
                                    <button type="button" class="text-xs text-portal-danger" @click="rows = rows.filter(r => r.id !== row.id)">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="submit" class="portal-btn-primary w-full">
                        Submit for review
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
