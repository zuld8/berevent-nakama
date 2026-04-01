<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'body_md', 'published_at', 'status', 'meta_title', 'meta_description', 'meta_image_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function setBodyMdAttribute($value): void
    {
        $processed = (string) $value;

        // 1) Rewrite <img src> to use media proxy for private S3 files uploaded via editor (pages/*)
        $processed = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($m) {
            $src = $m[1] ?? '';
            $path = null;
            if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                $urlPath = parse_url($src, PHP_URL_PATH);
                if ($urlPath) {
                    $part = ltrim($urlPath, '/');
                    $bucket = (string) config('filesystems.disks.s3.bucket');
                    if ($bucket !== '' && str_starts_with($part, $bucket . '/')) {
                        $part = substr($part, strlen($bucket) + 1);
                    }
                    if (str_starts_with($part, 'pages/') || str_starts_with($part, 'news/')) {
                        $path = $part;
                    }
                }
            } elseif (! empty($src)) {
                $part = ltrim($src, '/');
                $bucket = (string) config('filesystems.disks.s3.bucket');
                if ($bucket !== '' && str_starts_with($part, $bucket . '/')) {
                    $part = substr($part, strlen($bucket) + 1);
                }
                if (str_starts_with($part, 'pages/') || str_starts_with($part, 'news/')) {
                    $path = $part;
                }
            }
            if ($path) {
                $b64 = rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
                $proxy = route('media.proxy', ['disk' => 's3', 'p' => $b64]);
                return str_replace($src, $proxy, $m[0]);
            }
            return $m[0];
        }, $processed);

        // 2) Auto-embed YouTube/Vimeo links into responsive iframes
        $processed = preg_replace_callback('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>[^<]*<\/a>/i', function ($m) {
            $url = trim($m[1] ?? '');
            if ($url === '') return $m[0];
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $query = parse_url($url, PHP_URL_QUERY) ?: '';

            $embed = null;
            if (str_contains($host, 'youtube.com')) {
                parse_str($query, $q);
                $vid = $q['v'] ?? null;
                if ($vid) $embed = 'https://www.youtube.com/embed/' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
            } elseif (str_contains($host, 'youtu.be')) {
                $parts = array_values(array_filter(explode('/', $path)));
                $vid = $parts[0] ?? null;
                if ($vid) $embed = 'https://www.youtube.com/embed/' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
            } elseif (str_contains($host, 'vimeo.com')) {
                $parts = array_values(array_filter(explode('/', $path)));
                $vid = $parts[0] ?? null;
                if ($vid && ctype_digit($vid)) $embed = 'https://player.vimeo.com/video/' . $vid;
            }

            if ($embed) {
                return '<div class="relative" style="padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;"><iframe src="' . $embed . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe></div>';
            }
            return $m[0];
        }, $processed);

        $this->attributes['body_md'] = $processed;
    }

    public function getBodyHtmlAttribute(): string
    {
        $processed = (string) ($this->attributes['body_md'] ?? '');

        $processed = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($m) {
            $src = $m[1] ?? '';
            if ($src === '' || str_contains($src, '/media/')) return $m[0];
            $path = null;
            if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                $urlPath = parse_url($src, PHP_URL_PATH);
                if ($urlPath) {
                    $part = ltrim($urlPath, '/');
                    $bucket = (string) config('filesystems.disks.s3.bucket');
                    if ($bucket !== '' && str_starts_with($part, $bucket . '/')) {
                        $part = substr($part, strlen($bucket) + 1);
                    }
                    if (str_starts_with($part, 'pages/') || str_starts_with($part, 'news/')) {
                        $path = $part;
                    }
                }
            } else {
                $part = ltrim($src, '/');
                $bucket = (string) config('filesystems.disks.s3.bucket');
                if ($bucket !== '' && str_starts_with($part, $bucket . '/')) {
                    $part = substr($part, strlen($bucket) + 1);
                }
                if (str_starts_with($part, 'pages/') || str_starts_with($part, 'news/')) {
                    $path = $part;
                }
            }
            if ($path) {
                $b64 = rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
                $proxy = route('media.proxy', ['disk' => 's3', 'p' => $b64]);
                return str_replace($src, $proxy, $m[0]);
            }
            return $m[0];
        }, $processed);

        $processed = preg_replace_callback('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>[^<]*<\/a>/i', function ($m) {
            $url = trim($m[1] ?? '');
            if ($url === '') return $m[0];
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $query = parse_url($url, PHP_URL_QUERY) ?: '';
            $embed = null;
            if (str_contains($host, 'youtube.com')) {
                parse_str($query, $q);
                $vid = $q['v'] ?? null;
                if ($vid) $embed = 'https://www.youtube.com/embed/' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
            } elseif (str_contains($host, 'youtu.be')) {
                $parts = array_values(array_filter(explode('/', $path)));
                $vid = $parts[0] ?? null;
                if ($vid) $embed = 'https://www.youtube.com/embed/' . htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
            } elseif (str_contains($host, 'vimeo.com')) {
                $parts = array_values(array_filter(explode('/', $path)));
                $vid = $parts[0] ?? null;
                if ($vid && ctype_digit($vid)) $embed = 'https://player.vimeo.com/video/' . $vid;
            }
            if ($embed) {
                return '<div class="relative" style="padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;"><iframe src="' . $embed . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe></div>';
            }
            return $m[0];
        }, $processed);

        return $processed;
    }
}
