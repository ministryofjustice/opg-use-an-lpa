<?php

declare(strict_types=1);

namespace App\Service\ViewerCodes;

class CodeGenerator
{
    /**
     * The length of the code.
     */
    private const CODE_LENGTH = 12;

    /**
     * The characters allowed to appear in the code.
     */
    private const ALLOWED_CHARACTERS = '346789BCDFGHJKMPQRTVWXY';

    /**
     * Generates a random code of length CODE_LENGTH, using only the characters in ALLOWED_CHARACTERS.
     *
     * @return string
     * @throws \Exception
     */
    public static function generateCode(): string
    {
        $result = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $index = random_int(0, strlen(self::ALLOWED_CHARACTERS) - 1);
            $result .= self::ALLOWED_CHARACTERS[$index];
        }

        return $result;
    }
}
