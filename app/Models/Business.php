<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Business extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->BelongsToMany(
            User::class,
            'business_users',
            'business_id',
            'user_id',
            'external_id',
            'id'
        )->withPivot('user_external_id');
    }

    public function payItems(): HasMany
    {
        return $this->HasMany(
            PayItem::class,
            'business_id',
            'external_id',
        );
    }
}
