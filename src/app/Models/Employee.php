<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Employee extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'id_number', 'name', 'nickname',
        'department_id', 'section_id', 'position_id',
        'status_karyawan', 'gender',
        'tempat_lahir', 'tanggal_lahir', 'generation',
        'hire_date', 'contract_end_date', 'pendidikan', 'jurusan',
        'alamat_ktp', 'alamat_domisili', 'kota', 'provinsi', 'kode_pos', 'no_telepon',
        'performance_score', 'performance_category',
        'supervisor_id', 'user_id',
        'is_active', 'resign_date', 'resign_reason',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'resign_date' => 'date',
        'is_active' => 'boolean',
        'performance_score' => 'decimal:2',
    ];

    public static function resolveGeneration(int $year): string
    {
        return match (true) {
            $year >= 1997 => 'Gen Z',
            $year >= 1981 => 'Milenial',
            $year >= 1965 => 'Gen X',
            default => 'Baby Boomers',
        };
    }

    // ── Query ke replica untuk laporan berat ──────────────────────────
    public static function onReplica(): \Illuminate\Database\Eloquent\Builder
    {
        return self::on('mysql_replica');
    }

    // ── LogsActivity config ───────────────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'id_number', 'department_id', 'section_id', 'position_id',
                'status_karyawan', 'supervisor_id', 'is_active',
                'performance_score', 'performance_category',
                'resign_date', 'resign_reason',
            ])
            ->logOnlyDirty()          // hanya catat field yang berubah
            ->dontSubmitEmptyLogs()   // jangan catat jika tidak ada yang berubah
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Data karyawan {$this->name} dibuat",
                'updated' => "Data karyawan {$this->name} diperbarui",
                'deleted' => "Data karyawan {$this->name} dihapus",
                default => "Karyawan {$this->name}: {$eventName}",
            });
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
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    // ─── Auto Create User ────────────────────────────────────────────

    /**
     * Generate username dari nama (nama depan + tengah)
     */
    public function generateEmail(): string
    {
        $parts = explode(' ', trim($this->name));
        $firstName = mb_strtolower($parts[0] ?? 'user');
        $middleName = mb_strtolower($parts[1] ?? '');

        $base = $middleName ? $firstName.$middleName : $firstName;
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        // Cek duplikat, tambahkan angka increment kalau sudah ada
        $local = $base;
        $counter = 1;
        while (User::where('email', $local.'@smi.local')->exists()) {
            $local = $base.$counter;
            $counter++;
        }

        return $local.'@smi.local';
    }

    /**
     * Buat user untuk employee ini
     */
    public function createUser(): User
    {
        if ($this->user_id) {
            return $this->user;
        }

        $email = $this->generateEmail();
        $password = $this->tanggal_lahir ? $this->tanggal_lahir->format('dmY') : '01012000';

        $user = User::create([
            'name' => $this->name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $this->assignRoleToUser($user);

        $this->user_id = $user->id;
        $this->save();

        return $user;
    }

    /**
     * Cek dan buat user jika belum ada
     */
    public function createUserIfNeeded(): ?User
    {
        if ($this->user_id) {
            return $this->user;
        }

        return $this->createUser();
    }

    // __ Performance Rivew ________________________________________________
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function reviewedPerformanceReviews(): HasMany
    {
        return $this->hasMany(
            PerformanceReview::class,
            'reviewer_id'
        );
    }

    public function latestPerformanceReview()
    {
        return $this->hasOne(PerformanceReview::class)
            ->latestOfMany();
    }

    public function getLatestPerformanceScoreAttribute(): ?float
    {
        return $this->latestPerformanceReview?->final_score;
    }

    public function getLatestPerformanceCategoryAttribute(): ?string
    {
        return $this->latestPerformanceReview?->category;
    }

    // ── Accessor ──────────────────────────────────────────────────────
    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getMasaKerjaAttribute(): string
    {
        if (! $this->hire_date) {
            return '-';
        }
        $diff = $this->hire_date->diff(now());

        return $diff->y > 0
            ? "{$diff->y} tahun {$diff->m} bulan"
            : "{$diff->m} bulan";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_karyawan) {
            'PKWTT' => 'Karyawan Tetap (PKWTT)',
            'PKWT' => 'Karyawan Kontrak (PKWT)',
            'HARIAN' => 'Karyawan Harian',
            'DIREKTUR' => 'Direktur',
            default => $this->status_karyawan,
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

    // ── Boot: auto-set generation dan auto-create user ─────────────
    protected static function boot(): void
    {
        parent::boot();

        // Auto-set generation dari tanggal lahir
        self::saving(function (Employee $employee) {
            if ($employee->isDirty('tanggal_lahir') && $employee->tanggal_lahir) {
                $employee->generation = static::resolveGeneration(
                    (int) $employee->tanggal_lahir->format('Y')
                );
            }
        });

        // Auto-create user setelah employee dibuat
        self::created(function (Employee $employee) {
            // Hanya jika employee memiliki tanggal lahir dan posisi
            if ($employee->tanggal_lahir && $employee->position_id) {
                $employee->createUserIfNeeded();
            }
        });
    }

    /**
     * Assign role berdasarkan position
     */
    protected function assignRoleToUser(User $user): void
    {
        $position = $this->position;

        if (! $position) {
            $user->assignRole('karyawan');

            return;
        }

        // Mapping role berdasarkan position code atau level
        $roleName = match (true) {
            $position->code === 'HRGA' => 'hrd',
            $position->level === 'Kepala Bagian' => 'kepala_bagian',
            default => 'karyawan',
        };

        // Pastikan role ada di database
        if (! \Spatie\Permission\Models\Role::where('name', $roleName)->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => $roleName]);
        }

        $user->assignRole($roleName);
    }
}
