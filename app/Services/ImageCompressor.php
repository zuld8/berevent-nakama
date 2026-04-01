<?php

namespace App\Services;

class ImageCompressor
{
    /**
     * Compress an image (binary string) to a centered square JPEG under a target size.
     * Falls back safely if GD is unavailable or processing fails.
     */
    public static function squareJpegUnder(string $binary, int $maxBytes = 102400, int $maxDimension = 512): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $binary; // GD not available, return original
        }

        $src = @imagecreatefromstring($binary);
        if (! $src) {
            return $binary;
        }

        $w = imagesx($src); $h = imagesy($src);
        $size = min($w, $h);
        $sx = (int) max(0, ($w - $size) / 2);
        $sy = (int) max(0, ($h - $size) / 2);

        $dim = min($size, $maxDimension);

        // Iteratively reduce quality and then dimension
        $quality = 90;
        $attempts = 0;
        $minDim = 128;
        $result = null;
        while ($attempts < 12) {
            $canvas = imagecreatetruecolor($dim, $dim);
            imagecopyresampled($canvas, $src, 0, 0, $sx, $sy, $dim, $dim, $size, $size);

            ob_start();
            imagejpeg($canvas, null, $quality);
            $data = ob_get_clean();
            imagedestroy($canvas);

            if ($data !== false && strlen($data) <= $maxBytes) {
                $result = $data;
                break;
            }

            if ($quality > 55) {
                $quality -= 10;
            } else {
                $dim = (int) floor($dim * 0.85);
                if ($dim < $minDim) {
                    // give up; use the smallest we produced
                    $result = $data;
                    break;
                }
            }
            $attempts++;
        }

        imagedestroy($src);
        return $result ?: $binary;
    }

    /**
     * Compress an image to a target rectangle (cover crop) JPEG under a target size.
     */
    public static function rectangleJpegUnder(string $binary, int $maxBytes = 204800, int $targetW = 1280, int $targetH = 720): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $binary;
        }
        $src = @imagecreatefromstring($binary);
        if (! $src) return $binary;

        $w = imagesx($src); $h = imagesy($src);
        // compute cover crop to match aspect ratio targetW:targetH
        $targetRatio = $targetW / $targetH;
        $srcRatio = $w / $h;
        if ($srcRatio > $targetRatio) {
            // src too wide, crop width
            $newW = (int) round($h * $targetRatio);
            $newH = $h;
            $sx = (int) max(0, ($w - $newW) / 2);
            $sy = 0;
        } else {
            // src too tall, crop height
            $newW = $w;
            $newH = (int) round($w / $targetRatio);
            $sx = 0;
            $sy = (int) max(0, ($h - $newH) / 2);
        }

        $dimW = $targetW; $dimH = $targetH;
        $quality = 90; $attempts = 0;
        $result = null;
        while ($attempts < 12) {
            $canvas = imagecreatetruecolor($dimW, $dimH);
            imagecopyresampled($canvas, $src, 0, 0, $sx, $sy, $dimW, $dimH, $newW, $newH);
            ob_start();
            imagejpeg($canvas, null, $quality);
            $data = ob_get_clean();
            imagedestroy($canvas);
            if ($data !== false && strlen($data) <= $maxBytes) { $result = $data; break; }
            if ($quality > 55) { $quality -= 10; } else {
                $dimW = (int) floor($dimW * 0.9);
                $dimH = (int) floor($dimH * 0.9);
                if ($dimW < 640 || $dimH < 360) { $result = $data; break; }
            }
            $attempts++;
        }
        imagedestroy($src);
        return $result ?: $binary;
    }
}
