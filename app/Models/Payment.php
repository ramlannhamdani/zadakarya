<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = [
        'transfer' => 'Transfer Bank',
        'cash' => 'Tunai',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'order_id', 'invoice_id', 'amount', 'payment_date', 'method',
        'reference', 'note', 'proof_path', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
