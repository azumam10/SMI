<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PerformanceReview extends Model
{
    use HasFactory;

    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REVISED   = 'revised';

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'approved_by',
        'year',
        'semester',
        'discipline_score',
        'quality_score',
        'teamwork_score',
        'ethic_score',
        'initiative_score',
        'final_score',
        'category',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'year'          => 'integer',
        'semester'      => 'integer',
        'discipline_score' => 'decimal:2',
        'quality_score'    => 'decimal:2',
        'teamwork_score'   => 'decimal:2',
        'ethic_score'      => 'decimal:2',
        'initiative_score' => 'decimal:2',
        'final_score'      => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    // ─── Relasi ────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeBySupervisor(Builder $query, int $userId): Builder
    {
        // Penilaian yang dibuat oleh supervisor tertentu
        return $query->where('reviewer_id', $userId);
    }

    // ─── Helpers ────────────────────────────────────

    public static function calculateFinalScore(array $data): float
    {
        $score =
            ($data['discipline_score'] * 0.25)
            + ($data['quality_score'] * 0.30)
            + ($data['teamwork_score'] * 0.20)
            + ($data['ethic_score'] * 0.15)
            + ($data['initiative_score'] * 0.10);

        return round($score, 2);
    }

    public static function resolveCategory(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Outstanding',
            $score >= 80 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 60 => 'Fair',
            default      => 'Poor',
        };
    }
// 'Outstanding', 'Excellent', 'Good', 'Fair', 'Poor'

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'gray',
            self::STATUS_SUBMITTED => 'warning',
            self::STATUS_APPROVED  => 'success',
            self::STATUS_REVISED   => 'danger',
            default                => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_SUBMITTED => 'Menunggu Approval',
            self::STATUS_APPROVED  => 'Disetujui',
            self::STATUS_REVISED   => 'Revisi',
            default                => ucfirst($this->status),
        };
    }
}