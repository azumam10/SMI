<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'level', 'has_subordinates', 'is_active'];

    protected $casts = [
        'has_subordinates' => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function isKepala(): bool
    {
        return in_array($this->level, ['Direktur', 'Manager', 'Kepala Bagian', 'Supervisor'])
            || $this->has_subordinates;
    }
}