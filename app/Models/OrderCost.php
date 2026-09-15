<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCost extends Model
{
    public const CATEGORIES = [
        'kain' => 'Kain & Bahan Baku',
        'aksesoris' => 'Aksesoris & Trims',
        'makloon' => 'Jasa Makloon (Bordir/Sablon)',
        'cmt_jahit' => 'Upah CMT / Jahit',
        'packing_operasional' => 'Packing & Operasional',
    ];

    public const CATEGORY_COLORS = [
        'kain' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'aksesoris' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        'makloon' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'cmt_jahit' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'packing_operasional' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    ];

    protected $fillable = [
        'order_id',
        'category',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'receipt_path',
        'spent_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'spent_at' => 'date',
            'amount' => 'integer',
            'unit_price' => 'integer',
            'quantity' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
}
