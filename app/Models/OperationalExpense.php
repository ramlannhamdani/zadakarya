<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalExpense extends Model
{
    public const CATEGORIES = [
        'listrik_air' => 'Listrik, Air & Utilitas',
        'sewa_gedung' => 'Sewa Gedung & Workshop',
        'gaji_karyawan' => 'Gaji & Tunjangan Karyawan',
        'maintenance' => 'Pemeliharaan & Servis Mesin',
        'internet_pulsa' => 'Internet, Pulsa & Software',
        'operasional_kantor' => 'Operasional Kantor & ATK',
        'pemasaran_iklan' => 'Pemasaran & Iklan',
        'lain_lain' => 'Beban Umum Lainnya',
    ];

    public const CATEGORY_COLORS = [
        'listrik_air' => 'bg-amber-100 text-amber-800 border-amber-200',
        'sewa_gedung' => 'bg-purple-100 text-purple-800 border-purple-200',
        'gaji_karyawan' => 'bg-blue-100 text-blue-800 border-blue-200',
        'maintenance' => 'bg-orange-100 text-orange-800 border-orange-200',
        'internet_pulsa' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'operasional_kantor' => 'bg-slate-100 text-slate-800 border-slate-200',
        'pemasaran_iklan' => 'bg-pink-100 text-pink-800 border-pink-200',
        'lain_lain' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    public const METHODS = [
        'transfer' => 'Transfer Bank',
        'cash' => 'Tunai / Kas',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'reference_no',
        'category',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_path',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'integer',
        ];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryColorAttribute(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Generate unique reference number: EXP-YYYYMM-XXXX
     */
    public static function generateReferenceNo(): string
    {
        $prefix = 'EXP-' . now()->format('Ym') . '-';
        $latest = self::where('reference_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('reference_no');

        if ($latest && preg_match('/-(\d{4})$/', $latest, $m)) {
            $seq = (int) $m[1] + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
