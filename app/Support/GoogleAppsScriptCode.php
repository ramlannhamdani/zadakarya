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
}
