<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'quota', 'used'];

    protected $casts = [
        'quota' => 'decimal:1',
        'used' => 'decimal:1',
        'year' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Get remaining (otomatis dari kolom virtual)
    public function getRemainingAttribute()
    {
        return $this->quota - $this->used;
    }
}