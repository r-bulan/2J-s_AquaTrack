<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'barangay',
        'area',
        'jug_deposit',
        'status',
        'avg_reorder_days',
        'last_order_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'jug_deposit' => 'decimal:2',
            'avg_reorder_days' => 'integer',
            'last_order_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jugLedger()
    {
        return $this->hasOne(JugLedger::class);
    }

    public function creditLedger()
    {
        return $this->hasOne(CreditLedger::class);
    }

    public function loyaltyRecord()
    {
        return $this->hasOne(LoyaltyRecord::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->orderBy('order_date', 'desc');
    }

    public function recurringOrders()
    {
        return $this->hasMany(RecurringOrder::class)->orderBy('created_at', 'desc');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function isDueForRefill(): bool
    {
        if (!$this->last_order_date || !$this->avg_reorder_days) {
            return false;
        }

        $daysSince = (int) now()->diffInDays($this->last_order_date);
        return $daysSince >= ($this->avg_reorder_days - 1);
    }
}
