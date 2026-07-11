<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',

        'start_date',
        'end_date',

        'total_days',
        'approved_days',

        'reason',

        'status',
        'submitted_at',

        'supervisor_id',
        'supervisor_approved_at',
        'supervisor_note',

        'hrd_id',
        'hrd_approved_at',
        'hrd_note',

        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',

        'total_days' => 'decimal:1',
        'approved_days' => 'decimal:1',

        'submitted_at' => 'datetime',

        'supervisor_approved_at' => 'datetime',
        'hrd_approved_at' => 'datetime',

        'cancelled_at' => 'datetime',
    ];

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function hrd(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hrd_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LeaveDocument::class);
    }

    // Scope untuk filter status
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedSupervisorApproval($query)
    {
        return $query->where('status', 'pending')->whereNotNull('supervisor_id');
    }

    public function scopeNeedHrdApproval($query)
    {
        return $query->where('status', 'supervisor_approved')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')->whereNull('supervisor_id');
            });
    }

    // Helper status label
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => '⏳ Menunggu',
            'supervisor_approved' => '✅ Disetujui Atasan',
            'supervisor_rejected' => '❌ Ditolak Atasan',
            'hrd_approved' => '✅ Disetujui HRD',
            'hrd_rejected' => '❌ Ditolak HRD',
            'cancelled' => '🚫 Dibatalkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'supervisor_approved' => 'info',
            'supervisor_rejected' => 'danger',
            'hrd_approved' => 'success',
            'hrd_rejected' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
