<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayItem extends Model
{
    /** @use HasFactory<\Database\Factories\PayItemFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'business_id',
        'user_id',
        'amount',
        'rate',
        'hours',
        'item_date'
    ];

    protected function casts(): array
    {
        return ['item_date' => 'date:Y-m-d'];
    }
}
