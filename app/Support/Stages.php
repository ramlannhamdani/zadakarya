<?php

namespace App\Support;

class Stages
{
    /** The 7 official tracking stages. Order and names are locked by the PRD. */
    public const NAMES = [
        1 => 'Pesanan Diterima',
        2 => 'Desain Disetujui',
        3 => 'Bahan Disiapkan',
        4 => 'Proses Produksi',
        5 => 'Quality Check',
        6 => 'Siap Kirim',
        7 => 'Selesai',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public static function name(int $number): string
    {
        return self::NAMES[$number] ?? 'Tahap '.$number;
    }

    public static function count(): int
    {
        return count(self::NAMES);
    }
}
