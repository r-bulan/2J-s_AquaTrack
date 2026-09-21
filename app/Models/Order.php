<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'order_date',
        'status',
        'type',
        'jug_count',
        'round_count',
        'flat_count',
        'gallon_type',
        'unit_price',
        'round_unit_price',
        'flat_unit_price',
        'total_amount',
        'payment_status',
        'payment_method',
        'delivery_address',
        'preferred_time',
        'rider_id',
        'rider_name',
        'route_order',
        'recurring',
        'recurring_order_id',
        'notes',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'jug_count' => 'integer',
            'round_count' => 'integer',
            'flat_count' => 'integer',
            'unit_price' => 'decimal:2',
            'round_unit_price' => 'decimal:2',
            'flat_unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'route_order' => 'integer',
            'recurring' => 'boolean',
            'recurring_order_id' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function recurringOrder()
    {
        return $this->belongsTo(RecurringOrder::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
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
}
