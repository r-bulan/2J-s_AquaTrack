<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'quantity',
        'unit',
        'reorder_threshold',
        'last_restocked',
        'cost_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reorder_threshold' => 'integer',
            'last_restocked' => 'date',
            'cost_per_unit' => 'decimal:2',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_threshold;
    }
}
