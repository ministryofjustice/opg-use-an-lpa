<?php

declare(strict_types=1);

namespace Common\Filter;

use Exception;
use Laminas\Filter\FilterInterface;

class LpaUidFormat implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function filter($value): string
    {
        if (!is_string($value)) {
            throw new Exception('Invalid filter value - expecting string');
        }

        if (strlen($value) === 13 && ($value[0] === 'M' || $value[0] === 'm')) {
            return 'M-' . substr($value, 1, 4) . '-' . substr($value, 5, 4) . '-' . substr($value, 9, 4);
        }

        return $value;
    }
}
