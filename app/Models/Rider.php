<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'status',
        'wage_rate',
        'cash_advance',
        'area',
        'vehicle',
    ];

    protected function casts(): array
    {
        return [
            'wage_rate' => 'decimal:2',
            'cash_advance' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class)->orderBy('route_order', 'asc');
    }
}
