<?php

declare(strict_types=1);

namespace Common\Filter;

use Exception;
use Laminas\Filter\FilterInterface;

class ShareCodeFilter implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function filter($value): string
    {
        if (!is_string($value)) {
            throw new Exception('Invalid filter value - expecting string');
        }

        $value = preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?? $value;

        if (strlen($value) === 15 && $value[0] === 'P') {
            return sprintf(
                'P-%s-%s-%s-%s',
                substr($value, 1, 4),
                substr($value, 5, 4),
                substr($value, 9, 4),
                substr($value, 13, 2)
            );
        }

        if (strlen($value) === 13 && $value[0] === 'V') {
            return substr($value, 1, 12);
        }

        return $value;
    }
}
