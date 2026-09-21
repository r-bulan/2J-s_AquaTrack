<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    public const FAILURE_REASONS = [
        'Customer unavailable',
        'Wrong/incomplete address',
        'Customer refused delivery',
        'No safe access',
        'Vehicle/transport problem',
        'Water/product issue',
        'Other',
    ];

    public const RESOLUTION_PENDING_REVIEW = 'pending_review';
    public const RESOLUTION_RESCHEDULED = 'rescheduled';
    public const RESOLUTION_CANCELLED = 'cancelled';
    public const RESOLUTION_RESOLVED = 'resolved';

    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_phone',
        'address',
        'area',
        'rider_id',
        'rider_name',
        'route_order',
        'status',
        'failure_reason',
        'failure_notes',
        'failed_at',
        'failed_by_user_id',
        'retry_count',
        'failure_resolution',
        'resolved_at',
        'resolved_by_user_id',
        'delivery_date',
        'proof_photo',
        'signature',
        'notes',
        'jug_count',
        'round_count',
        'flat_count',
        'returned_jugs',
        'gallon_type',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'failed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'route_order' => 'integer',
            'retry_count' => 'integer',
            'jug_count' => 'integer',
            'round_count' => 'integer',
            'flat_count' => 'integer',
            'returned_jugs' => 'integer',
        ];
    }

    public function getBreakdownAttribute(): string
    {
        if ($this->round_count > 0 && $this->flat_count > 0) {
            return "{$this->round_count} Round + {$this->flat_count} Flat ({$this->jug_count} Jugs)";
        }
        if ($this->round_count > 0) {
            return "{$this->round_count}x Round Gallon" . ($this->round_count > 1 ? 's' : '');
        }
        if ($this->flat_count > 0) {
            return "{$this->flat_count}x Flat Gallon" . ($this->flat_count > 1 ? 's' : '');
        }
        return "{$this->jug_count}x {$this->gallon_type} Gallon" . ($this->jug_count > 1 ? 's' : '');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function attempts()
    {
        return $this->hasMany(DeliveryAttempt::class)->orderBy('attempt_number', 'asc');
    }

    public function latestAttempt()
    {
        return $this->hasOne(DeliveryAttempt::class)->latestOfMany('attempt_number');
    }

    public function failedByUser()
    {
        return $this->belongsTo(User::class, 'failed_by_user_id');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
