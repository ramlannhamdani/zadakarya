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

    public const TYPE_DP = 'dp';

    public const TYPE_SETTLEMENT = 'settlement';

    /**
     * Jenis pembayaran ditentukan admin saat mencatat, bukan ditebak dari
     * catatan — laporan DP, pelunasan, dan piutang semuanya bergantung padanya.
     */
    public const TYPES = [
        self::TYPE_DP => 'DP / Uang Muka',
        self::TYPE_SETTLEMENT => 'Pelunasan',
    ];

    protected $fillable = [
        'order_id', 'invoice_id', 'amount', 'type', 'payment_date', 'method',
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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_SETTLEMENT];
    }

    public function isDp(): bool
    {
        return $this->type === self::TYPE_DP;
    }
}
