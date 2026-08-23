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
