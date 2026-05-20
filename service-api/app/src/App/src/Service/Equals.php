<?php

declare(strict_types=1);

namespace App\Service;

final class Equals
{
    public static function firstNames(string $a, string $b): bool
    {
        return self::normaliseFirstNames($a) === self::normaliseFirstNames($b);
    }

    private static function normaliseFirstNames(string $s): string
    {
        // only take the first of the firstnames for comparison
        return self::turnUnicodeCharToAscii(strtolower(explode(' ', trim($s))[0]));
    }

    public static function lastName(string $a, string $b): bool
    {
        return self::normaliseLastName($a) === self::normaliseLastName($b);
    }

    private static function normaliseLastName(string $s): string
    {
        return self::turnUnicodeCharToAscii(preg_replace('/\s+/', ' ', strtolower(trim($s))));
    }

    public static function postcode(string $a, string $b): bool
    {
        return self::normalisePostcode($a) === self::normalisePostcode($b);
    }

    private static function normalisePostcode(string $s): string
    {
        return strtolower(str_replace(' ', '', $s));
    }

    private static function turnUnicodeCharToAscii(string $s): string
    {
        $s = str_replace(['‘', '’'], '\'', $s);
        $s = str_replace([
            "\u{2010}", // (the other unicode) hyphen
            "\u{2011}", // non-breaking hyphen
            "\u{2012}", // figure dash
            "\u{2013}", // en dash
            "\u{2014}", // em dash
        ], '-', $s);
        return $s;
    }
}
