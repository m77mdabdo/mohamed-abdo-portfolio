<?php

namespace App\Support;

/** Phone numbers typed in the dashboard → tel: and WhatsApp links. Saudi local numbers (05…) become +9665…. */
final class Phone
{
    /** International digits only, e.g. "966537514589", or null when the number is not usable. */
    public static function international(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '05') && strlen($digits) === 10) {
            $digits = '966'.substr($digits, 1);
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }

    public static function tel(?string $phone): ?string
    {
        $n = self::international($phone);

        return $n ? 'tel:+'.$n : null;
    }

    public static function whatsapp(?string $phone, string $message = ''): ?string
    {
        $n = self::international($phone);

        return $n ? 'https://wa.me/'.$n.($message !== '' ? '?text='.rawurlencode($message) : '') : null;
    }

    /** "+966 53 751 4589" style for display. */
    public static function display(?string $phone): string
    {
        $n = self::international($phone);
        if ($n && str_starts_with($n, '966') && strlen($n) === 12) {
            return '+966 '.substr($n, 3, 2).' '.substr($n, 5, 3).' '.substr($n, 8);
        }

        return $n ? '+'.$n : trim((string) $phone);
    }
}
