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
        'grand_total', 'dp_amount', 'amount_paid', 'payment_status',
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

    /** Recalculate grand total from items (items total minus nothing; discount lives on invoices). */
    public function refreshTotals(): void
    {
        $this->grand_total = (int) $this->items()->sum('total');
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

    public function logActivity(string $description, ?int $userId = null): void
    {
        $this->activities()->create([
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
