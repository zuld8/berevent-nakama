<?php

if (! function_exists('media_disk')) {
    /**
     * Return the preferred media storage disk name.
     * Uses S3 when AWS credentials are configured, otherwise falls back to 'public'.
     */
    function media_disk(): string
    {
        return env('AWS_ACCESS_KEY_ID') ? 's3' : 'public';
    }
}
