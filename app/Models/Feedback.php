<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'customer_id',
        'customer_name',
        'type',
        'rating',
        'message',
        'status',
        'admin_response',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
