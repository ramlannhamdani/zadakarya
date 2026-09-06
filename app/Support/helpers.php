<?php

use App\Models\Setting;

if (! function_exists('rupiah')) {
    function rupiah(int|float|null $value): string
    {
        return 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
    }
}

if (! function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('wa_link')) {
    function wa_link(?string $message = null): string
    {
        $number = preg_replace('/\D/', '', setting('whatsapp', '6281291002362'));
        $url = 'https://wa.me/'.$number;

        if ($message) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }
}

if (! function_exists('wa_number')) {
    /**
     * Normalkan nomor WhatsApp ke format internasional tanpa tanda plus,
     * mis. 0838-9535-2434 dan +62 838 9535 2434 sama-sama jadi 6283895352434.
     * Mengembalikan null bila isinya jelas bukan nomor telepon.
     */
    function wa_number(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.ltrim($digits, '0');
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }
}

if (! function_exists('wa_send_link')) {
    /**
     * Tautan "kirim pesan" WhatsApp. Tanpa nomor tujuan, WhatsApp akan meminta
     * pengguna memilih kontak sendiri — jadi tombolnya tetap berguna meski
     * nomor customer belum tersimpan.
     */
    function wa_send_link(?string $to, string $message): string
    {
        return 'https://wa.me/'.(wa_number($to) ?? '').'?text='.rawurlencode($message);
    }
}
