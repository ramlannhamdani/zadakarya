<?php

namespace App\Models;

use App\Support\Stages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPhoto extends Model
{
    protected $fillable = [
        'order_id', 'stage_number', 'image_path', 'thumb_path',
        'caption', 'visibility', 'uploaded_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function getStageNameAttribute(): string
    {
        return Stages::name($this->stage_number);
    }
}
