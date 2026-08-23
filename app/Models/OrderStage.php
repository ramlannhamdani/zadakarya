<?php

namespace App\Models;

use App\Support\Stages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStage extends Model
{
    protected $fillable = [
        'order_id', 'stage_number', 'name', 'status',
        'started_at', 'completed_at', 'note', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPending(): bool
    {
        return $this->status === Stages::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === Stages::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === Stages::STATUS_COMPLETED;
    }
}
