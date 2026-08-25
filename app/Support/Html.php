<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitasi HTML dari rich-text admin (artikel, deskripsi layanan).
 * Membuang <script>, <iframe>, atribut on*, javascript: URL, dsb.
 */
class Html
{
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null); // tanpa cache disk: aman untuk shared hosting
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'span', 'blockquote',
            'h2', 'h3', 'h4', 'ul', 'ol', 'li',
            'a[href|title|target|rel]', 'img[src|alt|width|height]',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ]));
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('AutoFormat.RemoveEmpty', true);

        return (new HTMLPurifier($config))->purify($html);
    }
}
