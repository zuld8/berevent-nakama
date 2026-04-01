@php
    $qrText = (string) ($qr ?? '');
    $imgSrc = $qrText !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($qrText)
        : null;
@endphp

<div class="space-y-3">
    @if($imgSrc)
        <div class="flex justify-center">
            <img src="{{ $imgSrc }}" alt="QR Code" class="rounded border border-gray-200 dark:border-gray-700" width="280" height="280">
        </div>
        <div class="text-center text-sm text-gray-500">
            Client: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $clientId }}</span>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">QR Text</label>
            <textarea class="w-full text-xs bg-gray-50 dark:bg-gray-900 rounded p-2" rows="3" readonly>{{ $qrText }}</textarea>
        </div>
    @else
        <div class="text-center text-sm text-gray-500">
            QR tidak tersedia untuk client ini.
        </div>
    @endif
</div>

