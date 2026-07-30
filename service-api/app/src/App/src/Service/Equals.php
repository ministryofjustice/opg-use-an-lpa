<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\EqualsNormalisationException;

final class Equals
{
    /**
     * @throws EqualsNormalisationException
     */
    public static function firstNames(string $a, string $b): bool
    {
        return self::normaliseFirstNames($a) === self::normaliseFirstNames($b);
    }

    /**
     * @throws EqualsNormalisationException
     */
    private static function normaliseFirstNames(string $s): string
    {
        $name = mb_strtolower(explode(' ', trim($s))[0]);

        if ($name === null) {
            throw new EqualsNormalisationException('Failed to normalise first name.');
        }

        // only take the first of the firstnames for comparison
        return self::turnUnicodeCharToAscii($name);
    }

    /**
     * @throws EqualsNormalisationException
     */
    public static function lastName(string $a, string $b): bool
    {
        return self::normaliseLastName($a) === self::normaliseLastName($b);
    }

    /**
     * @throws EqualsNormalisationException
     */
    private static function normaliseLastName(string $s): string
    {
        $name = preg_replace('/\s+/', ' ', mb_strtolower(trim($s)));

        if ($name === null) {
            throw new EqualsNormalisationException('Failed to normalise last name.');
        }

        return self::turnUnicodeCharToAscii();
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
