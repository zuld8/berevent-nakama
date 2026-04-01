<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class WaService
{
    public function getConfig(): array
    {
        $org = Organization::query()->first();
        $meta = $org?->meta_json ?? [];
        $cfg = Arr::get($meta, 'integrations.wa_service', Arr::get($meta, 'wa_service', []));

        return [
            'url' => rtrim((string)($cfg['url'] ?? 'http://localhost:3100'), '/'),
            'type_secret' => (string)($cfg['type_secret'] ?? 'headers'),
            'headers' => (array)($cfg['headers'] ?? Arr::get($cfg, 'value_secret', [])),
            'validate_client_id' => (string)($cfg['validate_client_id'] ?? ''),
            'validate_enabled' => (bool)($cfg['validate_enabled'] ?? false),
            'send_enabled' => (bool)($cfg['send_enabled'] ?? false),
            'send_client_id' => (string)($cfg['send_client_id'] ?? ''),
            // Backward comp: keep legacy key and provide split templates
            'message_template' => (string)($cfg['message_template'] ?? ''),
            'message_template_initiated' => (string)($cfg['message_template_initiated'] ?? ($cfg['message_template'] ?? '')),
            'message_template_paid' => (string)($cfg['message_template_paid'] ?? ''),
            'send_max_attempts' => (int)($cfg['send_max_attempts'] ?? 8),
        ];
    }

    protected function client(string $baseUrl)
    {
        $config = $this->getConfig();

        $req = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout(10);

        if (str_starts_with($baseUrl, 'https://') && preg_match('~^https://(localhost|127\.0\.0\.1)~', $baseUrl)) {
            $req = $req->withoutVerifying();
        }

        if (($config['type_secret'] ?? 'headers') === 'headers') {
            $headers = (array) ($config['headers'] ?? []);
            if (! empty($headers)) {
                $req = $req->withHeaders($headers);
            }
        }

        return $req;
    }

    public function listAccounts(): array
    {
        $config = $this->getConfig();
        $primaryUrl = $config['url'];

        $accounts = [];
        $triedFallback = false;
        $base = $primaryUrl;

        retry:
        try {
            $res = $this->client($base)->get('/accounts');
            if ($res->successful()) {
                $body = $res->json();
                if (is_array($body)) {
                    $data = array_values(is_assoc($body) ? ($body['data'] ?? []) : $body);
                    $accounts = array_map(function ($row) {
                        return [
                            'id' => $row['id'] ?? null,
                            'clientId' => $row['clientId'] ?? ($row['client_id'] ?? null),
                            'status' => $row['status'] ?? null,
                            'lastConnectedAt' => $row['lastConnectedAt'] ?? null,
                            'lastDisconnectedAt' => $row['lastDisconnectedAt'] ?? null,
                            'lastMessageAt' => $row['lastMessageAt'] ?? null,
                            'lastQr' => $row['lastQr'] ?? null,
                            'createdAt' => $row['createdAt'] ?? null,
                            'updatedAt' => $row['updatedAt'] ?? null,
                        ];
                    }, $data);
                }
            }
        } catch (\Throwable $e) {
            if (! $triedFallback && str_starts_with($primaryUrl, 'https://')) {
                $triedFallback = true;
                $base = 'http://' . ltrim(substr($primaryUrl, strlen('https://')), '/');
                goto retry;
            }
        }

        return $accounts;
    }

    public function startAccount(string $clientId): array
    {
        $config = $this->getConfig();
        $base = $config['url'];

        try {
            $res = $this->client($base)->post("/accounts/{$clientId}/start", []);
            if ($res->successful()) {
                $body = $res->json();
                $row = is_array($body) ? (is_assoc($body) ? ($body['data'] ?? $body) : ($body[0] ?? [])) : [];
                if (is_array($row)) {
                    return [
                        'id' => $row['id'] ?? null,
                        'clientId' => $row['clientId'] ?? ($row['client_id'] ?? null),
                        'status' => $row['status'] ?? null,
                        'lastConnectedAt' => $row['lastConnectedAt'] ?? null,
                        'lastDisconnectedAt' => $row['lastDisconnectedAt'] ?? null,
                        'lastMessageAt' => $row['lastMessageAt'] ?? null,
                        'lastQr' => $row['lastQr'] ?? null,
                        'createdAt' => $row['createdAt'] ?? null,
                        'updatedAt' => $row['updatedAt'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // swallow and let caller decide
        }

        return [];
    }

    public function deleteAccount(string $clientId): bool
    {
        $config = $this->getConfig();
        $base = $config['url'];

        try {
            $res = $this->client($base)->delete("/accounts/{$clientId}");
            return $res->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getQr(string $clientId): array
    {
        $config = $this->getConfig();
        $base = $config['url'];

        try {
            $res = $this->client($base)->get("/accounts/{$clientId}/qr");
            if ($res->successful()) {
                $body = $res->json();
                if (is_array($body)) {
                    $data = is_assoc($body) ? ($body['data'] ?? []) : ($body[0] ?? []);
                    if (is_array($data)) {
                        return [
                            'clientId' => (string) ($data['clientId'] ?? $clientId),
                            'qr' => (string) ($data['qr'] ?? ''),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    public function reconnectAccount(string $clientId): array
    {
        $config = $this->getConfig();
        $base = $config['url'];

        try {
            $res = $this->client($base)->post("/accounts/{$clientId}/reconnect", []);
            if ($res->successful()) {
                $body = $res->json();
                $row = is_array($body) ? (is_assoc($body) ? ($body['data'] ?? $body) : ($body[0] ?? [])) : [];
                if (is_array($row)) {
                    return [
                        'id' => $row['id'] ?? null,
                        'clientId' => $row['clientId'] ?? ($row['client_id'] ?? null),
                        'status' => $row['status'] ?? null,
                        'lastConnectedAt' => $row['lastConnectedAt'] ?? null,
                        'lastDisconnectedAt' => $row['lastDisconnectedAt'] ?? null,
                        'lastMessageAt' => $row['lastMessageAt'] ?? null,
                        'lastQr' => $row['lastQr'] ?? null,
                        'createdAt' => $row['createdAt'] ?? null,
                        'updatedAt' => $row['updatedAt'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // swallow and let caller decide
        }

        return [];
    }

    /**
     * Validate a phone number against a specific WA account session.
     * If clientId is null, uses configured 'validate_client_id'.
     * Returns normalized data or [].
     */
    public function validateNumber(string $number, ?string $clientId = null): array
    {
        $config = $this->getConfig();
        $base = $config['url'];
        $clientId = $clientId ?: ($config['validate_client_id'] ?? '');
        if ($clientId === '') {
            return [];
        }

        try {
            $res = $this->client($base)->get("/accounts/{$clientId}/validate-number", [
                'number' => $number,
            ]);
            if ($res->successful()) {
                $body = $res->json();
                $data = is_array($body) ? (is_assoc($body) ? ($body['data'] ?? []) : ($body[0] ?? [])) : [];
                if (is_array($data)) {
                    return [
                        'input' => (string)($data['input'] ?? $number),
                        'number' => (string)($data['number'] ?? ''),
                        'waId' => (string)($data['waId'] ?? ($data['wid'] ?? '')),
                        'isRegistered' => (bool)($data['isRegistered'] ?? false),
                        'wid' => (string)($data['wid'] ?? ($data['waId'] ?? '')),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    /**
     * Send a plain text WhatsApp message using configured WA service.
     * Returns true on HTTP 2xx response.
     */
    public function sendText(string $number, string $message, ?string $clientId = null): bool
    {
        $config = $this->getConfig();
        $base = $config['url'];
        $clientId = $clientId ?: ($config['send_client_id'] ?? '');
        if ($clientId === '' || trim($number) === '' || trim($message) === '') {
            return false;
        }

        // According to WA service spec: POST /messages
        $payload = [
            'clientId' => $clientId,
            'to' => $number,
            'text' => $message,
            'maxAttempts' => (int)($config['send_max_attempts'] ?? 8),
        ];

        $primaryUrl = $base;
        $triedFallback = false;
        retry:
        try {
            $res = $this->client($base)->post('/messages', $payload);
            if (! $res->successful()) return false;
            $body = $res->json();
            // Treat queued/ok as success
            $status = is_array($body) ? (data_get($body, 'data.status') ?: data_get($body, 'status')) : null;
            return $status ? true : true; // 2xx is enough; keep simple
        } catch (\Throwable $e) {
            if (! $triedFallback && str_starts_with($primaryUrl, 'https://')) {
                $triedFallback = true;
                $base = 'http://' . ltrim(substr($primaryUrl, strlen('https://')), '/');
                goto retry;
            }
            return false;
        }
    }

    /**
     * Replace placeholders in a template with values.
     * Placeholders use {key} format.
     * If template is HTML, convert to plain text with newlines.
     */
    public function renderTemplate(string $template, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }
        $rendered = strtr($template, $map);
        return $this->htmlToText($rendered);
    }

    protected function htmlToText(string $html): string
    {
        // Normalize CRLF/CR to LF
        $text = preg_replace("/\r\n|\r/", "\n", $html) ?? $html;

        // Convert key HTML semantics to plain text newlines
        $patterns = [
            // Close block elements => double newline (paragraph spacing)
            '/<\/(p|div|h[1-6]|blockquote)>/i' => "\n\n",
            // Line breaks
            '/<br\s*\/?\>/i' => "\n",
            // Lists
            '/<li[^>]*>/i' => "- ",
            '/<\/li>/i' => "\n",
            '/<\/(ul|ol)>/i' => "\n\n",
            '/<(ul|ol)[^>]*>/i' => "\n",
        ];
        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        // Strip any remaining tags
        $text = strip_tags($text);

        // Decode entities and normalize NBSP
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        // Collapse excessive blank lines to at most one empty line between paragraphs
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        // Trim line endings but preserve empty lines
        $lines = array_map(fn ($l) => rtrim($l), explode("\n", $text));
        $text = implode("\n", $lines);

        return trim($text);
    }
}

if (! function_exists('is_assoc')) {
    function is_assoc(array $arr): bool
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
