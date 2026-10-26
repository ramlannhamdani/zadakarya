<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvectionAsset extends Model
{
    public const CATEGORY_MACHINERY = 'machinery';
    public const CATEGORY_CUTTING_FINISHING = 'cutting_finishing';
    public const CATEGORY_PRINTING_EMBROIDERY = 'printing_embroidery';
    public const CATEGORY_FINISHING_STEAMER = 'finishing_steamer';
    public const CATEGORY_OFFICE_TECH = 'office_tech';
    public const CATEGORY_FACILITY_VEHICLE = 'facility_vehicle';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_MACHINERY => 'Mesin Jahit & Obras',
        self::CATEGORY_CUTTING_FINISHING => 'Mesin Potong & Meja Pola',
        self::CATEGORY_PRINTING_EMBROIDERY => 'Sablon, Heatpress & Bordir',
        self::CATEGORY_FINISHING_STEAMER => 'Setrika Uap & Finishing',
        self::CATEGORY_OFFICE_TECH => 'Komputer Desain & Elektronik',
        self::CATEGORY_FACILITY_VEHICLE => 'Fasilitas Workshop & Kendaraan',
        self::CATEGORY_OTHER => 'Aset & Inventaris Lainnya',
    ];

    public const CATEGORY_COLORS = [
        self::CATEGORY_MACHINERY => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        self::CATEGORY_CUTTING_FINISHING => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        self::CATEGORY_PRINTING_EMBROIDERY => 'bg-purple-50 text-purple-700 border-purple-200',
        self::CATEGORY_FINISHING_STEAMER => 'bg-orange-50 text-orange-700 border-orange-200',
        self::CATEGORY_OFFICE_TECH => 'bg-blue-50 text-blue-700 border-blue-200',
        self::CATEGORY_FACILITY_VEHICLE => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        self::CATEGORY_OTHER => 'bg-slate-100 text-slate-700 border-slate-200',
    ];

    public const CONDITION_GOOD = 'good';
    public const CONDITION_FAIR = 'fair';
    public const CONDITION_MAINTENANCE = 'maintenance';
    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITIONS = [
        self::CONDITION_GOOD => 'Baik / Prima',
        self::CONDITION_FAIR => 'Cukup / Normal',
        self::CONDITION_MAINTENANCE => 'Perlu Servis',
        self::CONDITION_DAMAGED => 'Rusak',
    ];

    public const CONDITION_COLORS = [
        self::CONDITION_GOOD => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        self::CONDITION_FAIR => 'bg-blue-50 text-blue-700 border-blue-200',
        self::CONDITION_MAINTENANCE => 'bg-amber-50 text-amber-700 border-amber-200',
        self::CONDITION_DAMAGED => 'bg-rose-50 text-rose-700 border-rose-200',
    ];

    protected $fillable = [
        'code',
        'name',
        'category',
        'purchase_date',
        'quantity',
        'unit',
        'purchase_price',
        'current_value',
        'condition',
        'location',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'integer',
        'purchase_price' => 'integer',
        'current_value' => 'integer',
    ];

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
        return self::CATEGORY_COLORS[$this->category] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function getConditionColorAttribute(): string
    {
        return self::CONDITION_COLORS[$this->condition] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    }

    public function getDepreciationAmountAttribute(): int
    {
        return max(0, (int) $this->purchase_price - (int) $this->current_value);
    }

    public function getDepreciationPercentageAttribute(): float
    {
        if ($this->purchase_price <= 0) {
            return 0.0;
        }

        return round(($this->depreciation_amount / $this->purchase_price) * 100, 1);
    }

    public static function generateCode(): string
    {
        $maxId = (int) static::max('id');

        return 'AST-' . str_pad((string) ($maxId + 1), 3, '0', STR_PAD_LEFT);
    }
}
