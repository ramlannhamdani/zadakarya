<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'order_id', 'date', 'due_date', 'subtotal',
        'discount', 'additional_cost_label', 'additional_cost', 'grand_total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Nomor invoice mengikuti nomor pesanan (ZDK-XXXX-HHMMTT).
     * Invoice kedua dan seterusnya untuk pesanan yang sama diberi akhiran -2, -3, ...
     */
    public static function nextNumberFor(Order $order): string
    {
        $count = static::where('order_id', $order->id)->count();
        $number = $count === 0 ? $order->order_number : $order->order_number.'-'.($count + 1);

        while (static::where('invoice_number', $number)->exists()) {
            $count++;
            $number = $order->order_number.'-'.($count + 1);
        }

        return $number;
    }

    public function refreshTotals(): void
    {
        $this->subtotal = (int) $this->items()->sum('total');
        $this->grand_total = max(0, $this->subtotal - $this->discount + $this->additional_cost);
        $this->save();
    }
}
