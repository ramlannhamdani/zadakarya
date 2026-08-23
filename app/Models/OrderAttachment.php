<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAttachment extends Model
{
    public const CATEGORIES = [
        'design' => 'Desain',
        'mockup' => 'Mockup',
        'logo' => 'Logo',
        'approval' => 'Approval',
        'po' => 'Purchase Order',
        'payment_proof' => 'Bukti Pembayaran',
        'qc' => 'Foto QC',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'order_id', 'file_path', 'original_name', 'category', 'size', 'uploaded_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
