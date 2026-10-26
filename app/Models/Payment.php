<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'stripe_payment_intent_id',
        'amount',      // en la unidad mínima de la moneda (centavos)
        'currency',
        'status',      // succeeded | failed
        'email',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'integer',
        'paid_at' => 'datetime',
    ];
}
