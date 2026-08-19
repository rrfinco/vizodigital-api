<div class="space-y-3">
    @forelse ($documents as $document)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm dark:border-white/10">
            <div>
                <p class="font-medium">{{ $document->original_name }}</p>
                <p class="text-xs text-gray-500">
                    {{ $document->typeLabel() }}
                    · {{ strtoupper($document->status) }}
                    @if ($document->mime_type)
                        · {{ $document->mime_type }}
                    @endif
                </p>
                @if ($document->rejection_reason)
                    <p class="mt-1 text-xs text-danger-600">{{ $document->rejection_reason }}</p>
                @endif
            </div>
            <button
                type="button"
                wire:click="downloadTaxationDocument({{ $document->id }})"
                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20"
            >
                Download
            </button>
        </div>
    @empty
        <p class="text-sm text-gray-500">No documents uploaded yet.</p>
    @endforelse
</div>
