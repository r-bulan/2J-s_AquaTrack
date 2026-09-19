<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'amount_owed',
        'last_payment_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'last_payment_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
