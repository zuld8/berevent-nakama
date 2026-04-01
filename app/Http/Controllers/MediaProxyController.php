<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaProxyController extends Controller
{
    public function show(Request $request, string $disk)
    {
        $encoded = $request->query('p');
        if (! $encoded) {
            abort(404);
        }
        $path = base64_decode(strtr($encoded, '-_', '+/'));
        if (! $path) {
            abort(404);
        }

        // Prefer streaming the file through the app to avoid client-side signed URL issues
        try {
            $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
            $lastModified = Storage::disk($disk)->lastModified($path);
            $stream = Storage::disk($disk)->readStream($path);
            if ($stream === false) {
                throw new \RuntimeException('Stream failed');
            }
            return response()->stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=900',
                'Last-Modified' => gmdate('D, d M Y H:i:s', (int) $lastModified) . ' GMT',
            ]);
        } catch (\Throwable $e) {
            // Fallback to redirecting to a signed URL
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            try {
                $url = Storage::disk($disk)->temporaryUrl($path, now()->addSeconds($ttl));
            } catch (\Throwable $e) {
                $url = Storage::disk($disk)->url($path);
            }
            return redirect()->away($url);
        }
    }
}
