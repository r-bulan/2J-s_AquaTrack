<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JugLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'jugs_held',
        'jugs_borrowed_date',
        'deposit_status',
        'deposit_amount',
    ];

    protected function casts(): array
    {
        return [
            'jugs_held' => 'integer',
            'jugs_borrowed_date' => 'date',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
