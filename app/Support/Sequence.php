<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sequence
{
    /**
     * Get the next sequential number for a named counter (atomic, race-safe).
     * Must be called inside the transaction that persists the record using it.
     */
    public static function next(string $name): int
    {
        $row = DB::table('number_sequences')->where('name', $name)->lockForUpdate()->first();

        if (! $row) {
            DB::table('number_sequences')->insert([
                'name' => $name,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('number_sequences')->where('name', $name)->lockForUpdate()->first();
        }

        $next = $row->value + 1;

        DB::table('number_sequences')->where('name', $name)->update([
            'value' => $next,
            'updated_at' => now(),
        ]);

        return $next;
    }

    /**
     * Format: ZDK-XXXX-HHMMTT — XXXX berurutan, akhiran jam+menit+tanggal
     * pembuatan agar nomor tidak dapat ditebak dari urutannya.
     */
    public static function orderNumber(): string
    {
        return sprintf('ZDK-%04d-%s', self::next('order'), now()->format('Hid'));
    }

    public static function invoiceNumber(): string
    {
        return sprintf('INV-%04d', self::next('invoice'));
    }
}
