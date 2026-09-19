<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_name',
        'equipment_type',
        'install_date',
        'last_service_date',
        'next_service_date',
        'service_interval_days',
        'volume_processed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'install_date' => 'date',
            'last_service_date' => 'date',
            'next_service_date' => 'date',
            'service_interval_days' => 'integer',
        ];
    }

    public function isOverdue(): bool
    {
        return now()->startOfDay()->gt($this->next_service_date);
    }

    public function isDueSoon(): bool
    {
        if ($this->isOverdue()) {
            return false;
        }
        $daysUntil = now()->startOfDay()->diffInDays($this->next_service_date, false);
        return $daysUntil >= 0 && $daysUntil <= 14;
    }

    public function getServiceStatusAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'Overdue';
        }
        if ($this->isDueSoon()) {
            return 'Due Soon';
        }
        return 'Good';
    }
}
