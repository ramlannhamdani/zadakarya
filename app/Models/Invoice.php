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

    /**
     * Selaraskan invoice lama (mis. INV-0004) dengan nomor pesanannya.
     * Dipanggil sekali oleh migrasi; aman diulang — hanya menyentuh nomor yang belum sesuai.
     * Mengembalikan jumlah invoice yang diubah.
     */
    public static function renumberLegacy(): int
    {
        $changed = 0;

        static::with('order')->orderBy('id')->get()->groupBy('order_id')->each(function ($invoices) use (&$changed) {
            $order = $invoices->first()->order;
            if (! $order) {
                return;
            }

            $base = $order->order_number;
            $conforms = fn (string $n) => $n === $base || str_starts_with($n, $base.'-');
            $used = $invoices->pluck('invoice_number')->filter($conforms)->all();

            foreach ($invoices as $invoice) {
                if ($conforms($invoice->invoice_number)) {
                    continue;
                }

                $i = 1;
                do {
                    $candidate = $i === 1 ? $base : $base.'-'.$i;
                    $i++;
                } while (in_array($candidate, $used, true) || static::where('invoice_number', $candidate)->exists());

                $invoice->update(['invoice_number' => $candidate]);
                $used[] = $candidate;
                $changed++;
            }
        });

        return $changed;
    }

    public function refreshTotals(): void
    {
        $this->subtotal = (int) $this->items()->sum('total');
        $this->grand_total = max(0, $this->subtotal - $this->discount + $this->additional_cost);
        $this->save();
    }
}
