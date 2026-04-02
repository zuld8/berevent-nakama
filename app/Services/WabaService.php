<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;

class WabaService
{
    protected string $baseUrl = 'https://chat.replai.id/api-app/waba';

    /**
     * Get WABA configuration from the first organization's meta_json.
     */
    public function getConfig(): array
    {
        $org = Organization::query()->first();
        $meta = $org?->meta_json ?? [];
        $cfg = Arr::get($meta, 'integrations.waba', []);

        return [
            'device_key'   => (string) ($cfg['device_key'] ?? ''),
            'api_key'      => (string) ($cfg['api_key'] ?? ''),
            'enabled'      => (bool) ($cfg['enabled'] ?? false),
            // Template IDs
            'template_welcome'   => (string) ($cfg['template_welcome'] ?? ''),
            'template_paid'      => (string) ($cfg['template_paid'] ?? ''),
            'template_followup1' => (string) ($cfg['template_followup1'] ?? ''),
            'template_followup2' => (string) ($cfg['template_followup2'] ?? ''),
            'template_followup3' => (string) ($cfg['template_followup3'] ?? ''),
            // Template language
            'template_lang' => (string) ($cfg['template_lang'] ?? 'id'),
        ];
    }

    /**
     * Send a WABA template message.
     *
     * @param string $phone  Recipient phone number
     * @param string $templateId  WABA template ID
     * @param array  $bodyParams  Array of body parameter values
     * @param array  $header  Optional header (type, value, filename)
     * @param array  $buttons Optional buttons array
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function sendTemplate(
        string $phone,
        string $templateId,
        array $bodyParams = [],
        array $header = [],
        array $buttons = [],
        ?string $lang = null
    ): array {
        $config = $this->getConfig();

        if (! $config['enabled'] || empty($config['device_key']) || empty($config['api_key'])) {
            return ['success' => false, 'message_id' => null, 'error' => 'WABA not configured'];
        }

        if (empty($phone) || empty($templateId)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Missing phone or template_id'];
        }

        $payload = [
            'device_key'    => $config['device_key'],
            'api_key'       => $config['api_key'],
            'phone'         => $this->normalizePhone($phone),
            'template_id'   => $templateId,
            'template_lang' => $lang ?? $config['template_lang'],
        ];

        if (! empty($bodyParams)) {
            $payload['body'] = array_values($bodyParams);
        }

        if (! empty($header)) {
            $payload['header'] = $header;
        }

        if (! empty($buttons)) {
            $payload['buttons'] = $buttons;
        }

        try {
            $res = Http::timeout(15)
                ->acceptJson()
                ->post("{$this->baseUrl}/messages/template", $payload);

            $body = $res->json();

            if ($res->successful() && ($body['status'] ?? false)) {
                $messageId = data_get($body, 'data.messages.0.id');
                Log::info('WABA template sent', [
                    'phone' => $phone,
                    'template' => $templateId,
                    'message_id' => $messageId,
                ]);
                return ['success' => true, 'message_id' => $messageId, 'error' => null];
            }

            $error = $body['message'] ?? 'Unknown error';
            Log::warning('WABA send failed', ['phone' => $phone, 'error' => $error, 'body' => $body]);
            return ['success' => false, 'message_id' => null, 'error' => $error];

        } catch (\Throwable $e) {
            Log::error('WABA exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a follow-up message for an Order or Donation.
     *
     * @param string $type  'w', 'fu1', 'fu2', 'fu3', 'paid'
     * @param string $phone Recipient phone number
     * @param array  $vars  Template body variables
     */
    public function sendFollowUp(string $type, string $phone, array $vars = []): array
    {
        $config = $this->getConfig();

        $templateKey = match ($type) {
            'w'    => 'template_welcome',
            'fu1'  => 'template_followup1',
            'fu2'  => 'template_followup2',
            'fu3'  => 'template_followup3',
            'paid' => 'template_paid',
            default => null,
        };

        if (! $templateKey || empty($config[$templateKey])) {
            return ['success' => false, 'message_id' => null, 'error' => "Template '{$type}' not configured"];
        }

        return $this->sendTemplate($phone, $config[$templateKey], $vars);
    }

    /**
     * Normalize phone number to international format (628xxx).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        } elseif (str_starts_with($phone, '+62')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }
}
