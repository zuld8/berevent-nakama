@if (!empty($previewUrl ?? null))
    <div class="rounded-lg border border-gray-200 bg-white p-2">
        <div class="mb-2 text-xs text-gray-500">Preview saat ini</div>
        <img src="{{ $previewUrl }}" alt="Hero" class="max-h-48 rounded-md" />
    </div>
@endif
