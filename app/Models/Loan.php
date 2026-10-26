<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';

    public const STATUSES = [
        self::STATUS_UNPAID => 'Belum Lunas',
        self::STATUS_PARTIAL => 'Dicicil Sebagian',
        self::STATUS_PAID => 'Lunas',
    ];

    public const STATUS_COLORS = [
        self::STATUS_UNPAID => 'bg-rose-50 text-rose-700 border-rose-200',
        self::STATUS_PARTIAL => 'bg-amber-50 text-amber-700 border-amber-200',
        self::STATUS_PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];

    protected $fillable = [
        'reference_no',
        'lender_name',
        'amount',
        'loan_date',
        'due_date',
        'order_id',
        'purpose',
        'notes',
        'status',
        'amount_paid',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'amount_paid' => 'integer',
        'loan_date' => 'date',
        'due_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class)->orderBy('payment_date', 'desc');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    public function getRepaidPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 100.0;
        }

        return round(min(100.0, ($this->amount_paid / $this->amount) * 100), 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID && $this->due_date && $this->due_date->isPast();
    }

    public function recalculateStatus(): void
    {
        $totalPaid = (int) $this->payments()->sum('amount');
        $this->amount_paid = $totalPaid;

        if ($totalPaid <= 0) {
            $this->status = self::STATUS_UNPAID;
        } elseif ($totalPaid >= $this->amount) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'LOAN-' . now()->format('Ym') . '-';
        $last = self::where('reference_no', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->reference_no, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
