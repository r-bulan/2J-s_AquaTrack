<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'round_count',
        'flat_count',
        'frequency',
        'status',
        'next_order_date',
        'last_generated_date',
        'target_day',
        'payment_method',
        'delivery_address',
        'preferred_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'round_count' => 'integer',
            'flat_count' => 'integer',
            'target_day' => 'integer',
            'next_order_date' => 'date',
            'last_generated_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->orderBy('order_date', 'desc');
    }

    public function getTotalJugsAttribute(): int
    {
        return (int) ($this->round_count + $this->flat_count);
    }

    public function getBreakdownAttribute(): string
    {
        $parts = [];
        if ($this->round_count > 0) {
            $parts[] = "{$this->round_count} Round";
        }
        if ($this->flat_count > 0) {
            $parts[] = "{$this->flat_count} Flat";
        }

        if (empty($parts)) {
            return "0 Gallons";
        }

        return implode(' + ', $parts) . " ({$this->total_jugs} Jugs)";
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeDue(Builder $query, ?string $date = null): Builder
    {
        $targetDate = $date ?? now()->toDateString();
        return $query->where('status', 'Active')
            ->whereDate('next_order_date', '<=', $targetDate);
    }
}
