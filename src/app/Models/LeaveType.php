<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'quota_days', 'require_document', 'is_active'];

    protected $casts = [
        'require_document' => 'boolean',
        'is_active' => 'boolean',
        'quota_days' => 'integer',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
