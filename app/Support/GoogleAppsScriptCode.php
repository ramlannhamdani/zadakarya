<?php

namespace App\Support;

class GoogleAppsScriptCode
{
    public static function getScript(): string
    {
        $path = resource_path('views/admin/spreadsheet/code.js');

        if (file_exists($path)) {
            return (string) file_get_contents($path);
        }

        return '// Script file not found';
    }

    /**
     * Versi skrip yang ada di aplikasi. Dibandingkan dengan versi yang dilaporkan
     * Google saat tes koneksi, supaya ketahuan kalau skrip di sana belum
     * diperbarui — mengubah berkas ini tidak mengubah apa pun di akun Google.
     */
    public static function version(): ?string
    {
        if (preg_match("/SCRIPT_VERSION\s*=\s*'([^']+)'/", self::getScript(), $m)) {
            return $m[1];
        }

        return null;
    }
}
