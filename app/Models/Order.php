<?php

namespace App\Models;

use App\Support\Stages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'active' => 'Aktif',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Belum Dibayar',
        'partial' => 'DP',
        'paid' => 'Lunas',
    ];

    protected $fillable = [
        'order_number', 'customer_id', 'name', 'status', 'current_stage',
        'subtotal', 'discount', 'grand_total', 'dp_amount', 'amount_paid', 'payment_status',
        'deadline', 'estimated_completion', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'estimated_completion' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(OrderStage::class)->orderBy('stage_number');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderAttachment::class)->latest();
    }

    public function productionPhotos(): HasMany
    {
        return $this->hasMany(ProductionPhoto::class)->latest();
    }

    public function publicPhotos(): HasMany
    {
        return $this->hasMany(ProductionPhoto::class)
            ->where('visibility', 'public')
            ->orderBy('stage_number')
            ->orderBy('created_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(OrderCost::class)->orderBy('spent_at', 'desc')->orderBy('id', 'desc');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->latest('loan_date');
    }

    /* ------------------------------------------------------------------ */

    public function createInitialStages(): void
    {
        foreach (Stages::NAMES as $number => $name) {
            $this->stages()->create([
                'stage_number' => $number,
                'name' => $name,
                'status' => $number === 1 ? Stages::STATUS_IN_PROGRESS : Stages::STATUS_PENDING,
                'started_at' => $number === 1 ? now() : null,
            ]);
        }

        $this->update(['current_stage' => 1]);
    }

    /** Recalculate subtotal and grand total (subtotal minus discount). */
    public function refreshTotals(): void
    {
        $this->subtotal = (int) $this->items()->sum('total');
        $this->grand_total = max(0, $this->subtotal - (int) $this->discount);
        $this->save();
        $this->refreshPaymentStatus();
    }

    /** Recalculate paid amount and derive the payment status automatically. */
    public function refreshPaymentStatus(): void
    {
        $paid = (int) $this->payments()->sum('amount');

        $status = 'unpaid';
        if ($paid > 0 && $paid < $this->grand_total) {
            $status = 'partial';
        } elseif ($paid > 0 && $paid >= $this->grand_total) {
            $status = 'paid';
        }

        $this->update(['amount_paid' => $paid, 'payment_status' => $status]);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->grand_total - $this->amount_paid);
    }

    /** Uang muka yang benar-benar sudah diterima (bukan kolom rencana dp_amount). */
    public function getDpPaidAttribute(): int
    {
        return (int) $this->payments->where('type', Payment::TYPE_DP)->sum('amount');
    }

    public function getSettlementPaidAttribute(): int
    {
        return (int) $this->payments->where('type', '!=', Payment::TYPE_DP)->sum('amount');
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getCurrentStageNameAttribute(): string
    {
        return Stages::name($this->current_stage);
    }

    public function getTotalCostAttribute(): int
    {
        return (int) ($this->relationLoaded('costs') ? $this->costs->sum('amount') : $this->costs()->sum('amount'));
    }

    public function getGrossProfitAttribute(): int
    {
        return (int) ($this->grand_total - $this->total_cost);
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->grand_total <= 0) {
            return 0.0;
        }

        return round(($this->gross_profit / $this->grand_total) * 100, 1);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) ($this->relationLoaded('items') ? $this->items->sum('quantity') : $this->items()->sum('quantity'));
    }

    public function getCostPerUnitAttribute(): int
    {
        if ($this->total_quantity <= 0) {
            return 0;
        }

        return (int) round($this->total_cost / $this->total_quantity);
    }

    public function getProfitStatusAttribute(): string
    {
        $hasCosts = $this->relationLoaded('costs') ? $this->costs->isNotEmpty() : $this->costs()->exists();
        if (! $hasCosts) {
            return 'unset';
        }

        if ($this->gross_profit < 0) {
            return 'loss';
        }

        if ($this->profit_margin < 15) {
            return 'thin';
        }

        if ($this->profit_margin < 25) {
            return 'fair';
        }

        return 'healthy';
    }

    public function getProfitStatusMetaAttribute(): array
    {
        return match ($this->profit_status) {
            'healthy' => [
                'label' => 'Margin Sehat (≥25%)',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800',
                'color' => 'text-emerald-600 dark:text-emerald-400',
            ],
            'fair' => [
                'label' => 'Margin Cukup (15-24%)',
                'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-blue-300 dark:border-blue-800',
                'color' => 'text-blue-600 dark:text-blue-400',
            ],
            'thin' => [
                'label' => 'Margin Tipis (<15%)',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-amber-300 dark:border-amber-800',
                'color' => 'text-amber-600 dark:text-amber-400',
            ],
            'loss' => [
                'label' => 'Rugi (HPP > Omset)',
                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300 border-rose-300 dark:border-rose-800',
                'color' => 'text-rose-600 dark:text-rose-400',
            ],
            default => [
                'label' => 'Belum Ada Biaya',
                'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                'color' => 'text-slate-500 dark:text-slate-400',
            ],
        };
    }

    /** Arus kas riil pesanan saat ini (Total Pembayaran Masuk dikurangi Total Belanja HPP). */
    public function getNetCashFlowAttribute(): int
    {
        return (int) ($this->amount_paid - $this->total_cost);
    }

    /**
     * Dana talangan kas kantor yang terpakai untuk pesanan ini.
     * Bernilai positif jika belanja HPP melebihi pembayaran/DP yang sudah masuk.
     */
    public function getCashDeficitAttribute(): int
    {
        return max(0, $this->total_cost - $this->amount_paid);
    }

    /** Sisa uang DP/pembayaran setelah dipakai belanja HPP (jika masih surplus). */
    public function getCashSurplusAttribute(): int
    {
        return max(0, $this->amount_paid - $this->total_cost);
    }

    /** Apakah pesanan aktif ini sedang menombok uang kas kantor sebelum pelunasan. */
    public function getIsCashDeficitAttribute(): bool
    {
        return $this->status === 'active'
            && $this->payment_status !== 'paid'
            && $this->cash_deficit > 0;
    }

    /** Status & metadata posisi kas pesanan untuk badge dan penjelasan UI. */
    public function getCashStatusMetaAttribute(): array
    {
        if ($this->payment_status === 'paid') {
            return [
                'status' => 'settled',
                'label' => 'Lunas — Kas Aman',
                'short_label' => 'Lunas',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                'color' => 'text-emerald-600 dark:text-emerald-400',
                'icon' => 'check',
                'description' => 'Pesanan sudah dilunasi penuh. Seluruh belanja HPP tertutup dan laba bersih kas terealisasi.',
            ];
        }

        if ($this->total_cost <= 0) {
            return [
                'status' => 'pending_cost',
                'label' => $this->amount_paid > 0 ? 'DP Masuk — Belum Belanja' : 'Belum Ada Transaksi',
                'short_label' => $this->amount_paid > 0 ? 'DP Masuk' : 'Belum Ada Biaya',
                'badge' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                'color' => 'text-slate-500 dark:text-slate-400',
                'icon' => 'clock',
                'description' => 'Belum ada pengeluaran nota/biaya HPP yang dicatat untuk pesanan ini.',
            ];
        }

        if ($this->cash_deficit > 0) {
            return [
                'status' => 'deficit',
                'label' => 'Nombok Kas ' . rupiah($this->cash_deficit),
                'short_label' => 'Nombok ' . rupiah($this->cash_deficit),
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                'color' => 'text-rose-600 dark:text-rose-400',
                'icon' => 'alert-triangle',
                'description' => 'Belanja HPP melebihi DP masuk. Kas kantor menalangi ' . rupiah($this->cash_deficit) . ' sampai pelunasan.',
            ];
        }

        return [
            'status' => 'surplus',
            'label' => 'DP Cukup (Sisa ' . rupiah($this->cash_surplus) . ')',
            'short_label' => 'DP Cukup',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
            'color' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'shield-check',
            'description' => 'Uang muka mencukupi seluruh biaya belanja HPP saat ini. Kas kantor aman.',
        ];
    }

    public function logActivity(string $description, ?int $userId = null): void
    {
        $this->activities()->create([
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
