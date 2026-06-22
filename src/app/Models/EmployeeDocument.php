<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'category', 'label', 'keterangan'];

    public const CATEGORY_LABELS = [
        'foto'              => 'Foto Karyawan',
        'cv'                => 'Daftar Riwayat Hidup',
        'ktp'               => 'KTP',
        'personal_data'     => 'Data Personal',
        'tes_lapangan'      => 'Tes Lapangan',
        'hasil_wawancara'   => 'Hasil Wawancara',
        'skill_assessment'  => 'Skill Assessment',
        'kontrak_kerja'     => 'Kontrak Kerja',
        'surat_putusan'     => 'Surat Putusan Personalia',
        'surat_lamaran'     => 'Surat Lamaran Kerja',
        'surat_pengalaman'  => 'Surat Pengalaman Kerja',
        'training_program'  => 'Training Program',
        'sertifikat'        => 'Sertifikat Keahlian',
        'dokumen_pendukung' => 'Dokumen Pendukung Lain',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'employee_document_id')
            ->orderBy('sort_order');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function isFull(): bool
    {
        return $this->files()->count() >= 5;
    }
}