<?php

namespace App\Support;

class Messenger
{
    /**
     * Turn whatever the owner pasted (m.me link, facebook.com link, @handle, or plain username)
     * into a bare username. Returns null when nothing usable is found.
     */
    public static function normalize(?string $input): ?string
    {
        $value = trim((string) $input);

        if ($value === '') {
            return null;
        }

        if (preg_match('~^(?:https?://)?(?:www\.|m\.|web\.)?(?:m\.me|facebook\.com|fb\.com|messenger\.com/t)/([^/?#]+)~i', $value, $matches)) {
            $value = $matches[1];
        }

        $value = ltrim($value, '@');

        return preg_match('/^[A-Za-z0-9.]{1,50}$/', $value) ? $value : null;
    }
}
