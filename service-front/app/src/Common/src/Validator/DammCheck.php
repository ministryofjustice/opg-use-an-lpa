<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class DammCheck extends AbstractValidator
{
    public const INVALID_REFERENCE = 'invalidReference';

    private const TABLE = [
        [0, 3, 1, 7, 5, 9, 8, 6, 4, 2],
        [7, 0, 9, 2, 1, 5, 4, 8, 6, 3],
        [4, 2, 0, 6, 8, 7, 1, 3, 5, 9],
        [1, 7, 5, 0, 9, 8, 3, 4, 2, 6],
        [6, 1, 2, 3, 0, 4, 5, 9, 7, 8],
        [3, 6, 7, 4, 2, 0, 9, 5, 8, 1],
        [5, 8, 6, 9, 7, 2, 0, 1, 3, 4],
        [8, 9, 4, 5, 3, 6, 2, 0, 1, 7],
        [9, 4, 3, 8, 6, 1, 7, 2, 0, 5],
        [2, 5, 8, 1, 4, 3, 6, 7, 9, 0],
    ];

    /**
     * @var string[]
     */
    protected array $messageTemplates = [
        //  From parent
        self::INVALID_REFERENCE => 'The LPA reference number provided is not correct',
    ];

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        // Only apply to M-UIDs, as this will be used in places where 7-UIDs can also be provided.
        if (strlen($value) !== 13) {
            return true;
        }

        if ($value[0] !== 'M' && $value[0] !== 'm') {
            $this->error(self::INVALID_REFERENCE);
            return false;
        }

        $zero    = ord('0');
        $interim = 0;
        foreach (str_split(substr($value, 1, 12)) as $char) {
            $offset = ord($char) - $zero;
            if ($offset < 0 || $offset > 9) {
                $this->error(self::INVALID_REFERENCE);
                return false;
            }

            $interim = self::TABLE[$interim][$offset];
        }

        if ($interim !== 0) {
            $this->error(self::INVALID_REFERENCE);
            return false;
        }

        return true;
    }
}
