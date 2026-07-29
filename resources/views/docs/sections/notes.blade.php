<x-docs.section-frame :label="$label" :anchor="$anchor">
    <div class="space-y-4">
        @foreach ($notes as $note)
            <x-docs.markdown :html="$note['html']" />
        @endforeach
    </div>
</x-docs.section-frame>
