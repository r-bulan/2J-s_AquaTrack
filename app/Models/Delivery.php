<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

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
            'route_order' => 'integer',
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
}
