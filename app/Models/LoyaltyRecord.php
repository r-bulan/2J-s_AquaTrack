<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'refills_count',
        'refills_needed',
        'free_jugs_earned',
        'last_reward_date',
    ];

    protected function casts(): array
    {
        return [
            'refills_count' => 'integer',
            'refills_needed' => 'integer',
            'free_jugs_earned' => 'integer',
            'last_reward_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
