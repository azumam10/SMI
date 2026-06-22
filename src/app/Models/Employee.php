<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_number', 'name', 'nickname',
        'department_id', 'section_id', 'position_id',
        'status_karyawan', 'gender',
        'tempat_lahir', 'tanggal_lahir', 'generation',
        'hire_date', 'contract_end_date', 'pendidikan', 'jurusan',
        'alamat_ktp',  'no_telepon',
        'performance_score', 'performance_category',
        'supervisor_id', 'user_id',
        'is_active', 'resign_date', 'resign_reason',
    ];

    protected $casts = [
        'tanggal_lahir'     => 'date',
        'hire_date'         => 'date',
        'contract_end_date' => 'date',
        'resign_date'       => 'date',
        'is_active'         => 'boolean',
        'performance_score' => 'decimal:2',
    ];

    // ── Boot: auto-set generation berdasarkan tahun lahir ─────────────
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Employee $employee) {
            if ($employee->isDirty('tanggal_lahir') && $employee->tanggal_lahir) {
                $employee->generation = static::resolveGeneration(
                    (int) $employee->tanggal_lahir->format('Y')
                );
            }
        });
    }

    public static function resolveGeneration(int $year): string
    {
        return match (true) {
            $year >= 1997 => 'Gen Z',
            $year >= 1981 => 'Milenial',
            $year >= 1965 => 'Gen X',
            default       => 'Baby Boomers',
        };
    }

    // ── Relasi ────────────────────────────────────────────────────────
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    // ── Query ke replica untuk laporan berat ──────────────────────────
    public static function onReplica(): \Illuminate\Database\Eloquent\Builder
    {
        return static::on('mysql_replica');
    }

    // ── Accessor ──────────────────────────────────────────────────────
    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getMasaKerjaAttribute(): string
    {
        if (! $this->hire_date) return '-';
        $diff = $this->hire_date->diff(now());
        return $diff->y > 0
            ? "{$diff->y} tahun {$diff->m} bulan"
            : "{$diff->m} bulan";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_karyawan) {
            'PKWTT'    => 'Karyawan Tetap (PKWTT)',
            'PKWT'     => 'Karyawan Kontrak (PKWT)',
            'HARIAN'   => 'Karyawan Harian',
            'DIREKTUR' => 'Direktur',
            default    => $this->status_karyawan,
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBawahan($query, int $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }
}