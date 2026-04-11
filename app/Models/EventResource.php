<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class EventResource extends Model
{
    protected $fillable = [
        'event_id',
        'label',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'file_size',
        'sort',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort'      => 'integer',
    ];

    // ─── Relations ──────────────────────────────────────────

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // ─── Helpers ────────────────────────────────────────────

    /** Icon berdasarkan MIME type */
    public function icon(): string
    {
        $mime = (string) $this->mime_type;

        return match (true) {
            str_contains($mime, 'pdf')                          => '📄',
            str_contains($mime, 'presentation')
                || str_contains($mime, 'powerpoint')
                || str_ends_with($this->original_name ?? '', '.pptx')
                || str_ends_with($this->original_name ?? '', '.ppt')  => '📊',
            str_contains($mime, 'spreadsheet')
                || str_contains($mime, 'excel')
                || str_ends_with($this->original_name ?? '', '.xlsx') => '📗',
            str_contains($mime, 'word')
                || str_ends_with($this->original_name ?? '', '.docx') => '📝',
            str_contains($mime, 'zip')
                || str_contains($mime, 'rar')                  => '🗜️',
            str_contains($mime, 'image')                       => '🖼️',
            default                                             => '📁',
        };
    }

    /** Ukuran file human-readable */
    public function humanSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) return '';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    /** URL download (via controller, bukan langsung ke storage) */
    public function downloadRoute(Event $event): string
    {
        return route('event.resource.download', [$event->slug, $this->id]);
    }
}
