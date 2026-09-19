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
        'gallon_type',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'route_order' => 'integer',
            'jug_count' => 'integer',
        ];
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
