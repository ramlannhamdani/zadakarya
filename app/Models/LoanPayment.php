<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    public const METHODS = [
        'transfer' => 'Transfer Bank',
        'cash' => 'Tunai / Kas',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'loan_id',
        'reference_no',
        'payment_date',
        'amount',
        'payment_method',
        'notes',
        'attachment_path',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (LoanPayment $payment) {
            $payment->loan?->recalculateStatus();
        });

        static::deleted(function (LoanPayment $payment) {
            $payment->loan?->recalculateStatus();
        });

        static::updated(function (LoanPayment $payment) {
            $payment->loan?->recalculateStatus();
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'LPAY-' . now()->format('Ym') . '-';
        $last = self::where('reference_no', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->reference_no, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
