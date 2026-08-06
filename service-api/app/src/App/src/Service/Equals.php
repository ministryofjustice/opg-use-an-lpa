<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\EqualsNormalisationException;
use SebastianBergmann\CodeCoverage\CodeCoverage;

final class Equals
{
    public static function firstNames(string $a, string $b): bool
    {
        return self::normaliseFirstNames($a) === self::normaliseFirstNames($b);
    }

    private static function normaliseFirstNames(string $s): string
    {
        // only take the first of the firstnames for comparison
        return self::turnUnicodeCharToAscii(mb_strtolower(explode(' ', trim($s))[0]));
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

        // @codeCoverageIgnoreStart
        // Not possible to force preg_replace to return a null.
        if ($name === null) {
            throw new EqualsNormalisationException('Failed to normalise last name.');
        }
        // @codeCoverageIgnoreEnd

        return self::turnUnicodeCharToAscii($name);
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
