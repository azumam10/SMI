<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class LeaveDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'original_name', 'stored_name',
        'disk', 'path',
        'mime_type', 'size',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('leave-document.download', $this);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    protected static function boot(): void
    {
        parent::boot();
        self::deleting(function (LeaveDocument $doc) {
            Storage::disk($doc->disk)->delete($doc->path);
        });
    }
}
