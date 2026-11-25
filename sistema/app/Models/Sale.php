<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'user_id', 'date', 'total', 'igv'])]
class Sale extends Model
{
    /** Tasa de IGV (18%). Los precios de los medicamentos ya incluyen IGV. */
    public const IGV_RATE = 0.18;

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailSale::class);
    }
}
