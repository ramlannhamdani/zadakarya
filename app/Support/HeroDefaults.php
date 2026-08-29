<?php

namespace App\Support;

/**
 * Sumber tunggal teks bawaan hero halaman depan.
 * Dipakai seeder (mengisi baris settings), controller (fallback saat setting kosong),
 * dan form admin (placeholder) — supaya database lama tanpa key baru tetap tampil benar.
 */
final class HeroDefaults
{
    public const BADGE = 'Konveksi & Custom Pakaian Berkualitas';

    public const TITLE = "Buat Gayamu,\nKami Wujudkan";

    public const TITLE_ACCENT = 'Kualitas Tanpa Kompromi';

    public const TEXT = 'Konveksi terpercaya untuk kebutuhan pribadi, komunitas, event, dan brand kamu. Desain bebas, bahan pilihan, hasil rapi & nyaman dipakai.';

    public const RATING_TEXT = '4.9/5 dari 200+ pelanggan';

    public const RATING_SUBTEXT = 'Puas dengan hasil & pelayanan kami';

    public const STATS = "1000+ | Pelanggan Puas\n5000+ | Pesanan Selesai\n5+ Tahun | Pengalaman\nPengiriman | Seluruh Indonesia\nCustomer Service | Siap Membantu";

    /** Warna avatar pill rating (harus literal agar dipindai Tailwind). */
    public const AVATAR_COLORS = ['bg-brand-600', 'bg-warm-500', 'bg-brand-400'];

    /** Key setting => nilai bawaan (dipakai seeder & placeholder admin). */
    public static function all(): array
    {
        return [
            'hero_badge' => self::BADGE,
            'hero_title' => self::TITLE,
            'hero_title_accent' => self::TITLE_ACCENT,
            'hero_text' => self::TEXT,
            'hero_rating_text' => self::RATING_TEXT,
            'hero_stats' => self::STATS,
        ];
    }

    /**
     * Ubah teks multi-baris "Nilai | Label" menjadi maksimal 5 item statistik.
     * Baris tanpa "|" dianggap hanya nilai (label kosong).
     */
    public static function stats(?string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $raw) !== '' ? $raw : self::STATS) ?: [];

        $stats = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            [$value, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $stats[] = ['value' => $value, 'label' => $label];

            if (count($stats) === 5) {
                break;
            }
        }

        return $stats;
    }
}
