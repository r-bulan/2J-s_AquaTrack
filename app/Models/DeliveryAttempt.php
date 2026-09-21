<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'rider_id',
        'rider_name',
        'attempt_number',
        'status',
        'failure_reason',
        'failure_notes',
        'assigned_at',
        'started_at',
        'failed_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'failed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }
}
