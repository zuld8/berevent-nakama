<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" color="primary">
                Simpan
            </x-filament::button>
        </div>
    </form>
    <x-filament::section heading="Contoh Penggunaan" description="CURL untuk uji cepat">
        <pre class="text-sm">curl -X 'GET' \
  '{{ data_get($this->data, 'url', 'http://localhost:3100') }}/accounts' \
  -H 'accept: application/json' \
  -H '{{ array_key_first((array)($this->data['headers'] ?? ['x-api-key' => 'keyadmin'])) }}: {{ (array_values((array)($this->data['headers'] ?? ['x-api-key' => 'keyadmin'])))[0] }}'</pre>
    </x-filament::section>
</x-filament-panels::page>

