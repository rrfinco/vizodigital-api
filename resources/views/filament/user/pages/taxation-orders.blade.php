<x-filament-panels::page>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        After payment confirmation, upload supporting documents. Admin can then mark them verified and approved.
    </p>

    <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">Order</th>
                    <th class="px-3 py-2">Client</th>
                    <th class="px-3 py-2">Service</th>
                    <th class="px-3 py-2">Amount</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Documents</th>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($this->orders as $order)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs">
                            #{{ $order->id }}
                            <div class="text-gray-400">{{ $order->api_request_id }}</div>
                        </td>
                        <td class="px-3 py-2">
                            {{ $order->client?->fullName() }}
                            <div class="text-xs text-gray-500">ID {{ $order->taxation_client_id }}</div>
                        </td>
                        <td class="px-3 py-2">
                            {{ $order->service_name }}
                            <div class="text-xs text-gray-500">service_id {{ $order->taxation_service_id }}</div>
                        </td>
                        <td class="px-3 py-2">₹{{ number_format((float) $order->amount, 2) }}</td>
                        <td class="px-3 py-2 uppercase">{{ $order->status }}</td>
                        <td class="px-3 py-2">
                            <span class="uppercase">{{ $order->documents_status }}</span>
                            <div class="text-xs text-gray-500">{{ $order->documents->count() }} file(s)</div>
                            @if ($order->documents_status === 'rejected' && $order->documents_note)
                                <div class="mt-1 text-xs text-danger-600">{{ $order->documents_note }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $order->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-right">
                            @if ($order->canReceiveDocuments())
                                <button
                                    type="button"
                                    wire:click="startUpload({{ $order->id }})"
                                    class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100 dark:bg-teal-900/40 dark:text-teal-200"
                                >
                                    Upload
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($order->documents->isNotEmpty())
                        <tr>
                            <td colspan="8" class="bg-gray-50 px-3 py-2 text-xs dark:bg-white/5">
                                <ul class="space-y-1">
                                    @foreach ($order->documents as $document)
                                        <li>
                                            {{ $document->typeLabel() }}
                                            — {{ $document->original_name }}
                                            <span class="uppercase text-gray-500">({{ $document->status }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">No taxation orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->orders->links() }}
    </div>

    @if ($this->uploadOrderId)
        <div class="mt-6 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <h3 class="text-sm font-semibold">Upload document for order #{{ $this->uploadOrderId }}</h3>
            <p class="mt-1 text-xs text-gray-500">PDF or image, max 10 MB. You can upload multiple files one by one.</p>

            <form wire:submit="uploadDocument" class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Document type</label>
                    <select wire:model="documentType" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                        @foreach ($this->documentTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('documentType')
                        <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">File</label>
                    <input
                        type="file"
                        wire:model="documentFile"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-teal-700 dark:text-gray-300 dark:file:bg-teal-900/40 dark:file:text-teal-200"
                    />
                    <div wire:loading wire:target="documentFile" class="text-xs text-gray-500">Uploading…</div>
                    @error('documentFile')
                        <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-2 sm:col-span-2">
                    <x-filament::button type="submit" size="sm">
                        Submit document
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" size="sm" wire:click="cancelUpload">
                        Cancel
                    </x-filament::button>
                </div>
            </form>
        </div>
    @endif
</x-filament-panels::page>
