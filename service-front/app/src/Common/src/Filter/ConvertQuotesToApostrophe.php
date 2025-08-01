<?php

declare(strict_types=1);

namespace Common\Filter;

use Exception;
use Laminas\Filter\FilterInterface;

class ConvertQuotesToApostrophe implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function filter($value): string
    {
        if (!is_string($value)) {
            throw new Exception('Invalid filter value - expecting string');
        }

        return str_replace(['‘', '’'], "'", $value);
    }
}
