<div class="space-y-3">
    @forelse ($documents as $document)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm dark:border-white/10">
            <div>
                <p class="font-medium">{{ $document->original_name }}</p>
                <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $document->document_type) }} · {{ $document->mime_type }}</p>
            </div>
            <button
                type="button"
                wire:click="downloadKycDocument({{ $document->id }})"
                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20"
            >
                Download
            </button>
        </div>
    @empty
        <p class="text-sm text-gray-500">No documents uploaded.</p>
    @endforelse
</div>
