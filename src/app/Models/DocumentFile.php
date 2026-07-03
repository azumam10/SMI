<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User;


class DocumentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_document_id',
        'original_name', 'stored_name',
        'disk', 'path',
        'mime_type', 'size',
        'sort_order', 'uploaded_by',
    ];

    protected $casts = [
        'size'       => 'integer',
        'sort_order' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class, 'employee_document_id');
    }

    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function uploader(): BelongsTo
{
    return $this->belongsTo(User::class, 'uploaded_by');
}

public function getUploaderNameAttribute(): string
{
    return $this->uploader?->name ?? '-';
}

public function scopeOrdered($query)
{
    return $query->orderBy('sort_order');
}

   protected static function booted(): void
{
    static::deleting(function (DocumentFile $file): void {
        if (
            filled($file->disk) &&
            filled($file->path) &&
            \Storage::disk($file->disk)->exists($file->path)
        ) {
            \Storage::disk($file->disk)->delete($file->path);
        }
    });
}
}