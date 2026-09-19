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
        'gallon_type',
        'unit_price',
        'total_amount',
        'payment_status',
        'payment_method',
        'delivery_address',
        'preferred_time',
        'rider_id',
        'rider_name',
        'route_order',
        'recurring',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'jug_count' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'route_order' => 'integer',
            'recurring' => 'boolean',
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

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
