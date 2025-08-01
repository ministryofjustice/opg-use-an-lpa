<?php

declare(strict_types=1);

namespace Common\Filter;

use Exception;
use Laminas\Filter\FilterInterface;

class StripSpacesAndHyphens implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function filter($value): string
    {
        if (!is_string($value)) {
            throw new Exception('Invalid filter value - expecting string');
        }

        // strip out whitespace
        $value = str_replace(' ', '', $value);
        // strip out en dash
        $value = str_replace('–', '', $value);
        // strip out em dash
        $value = str_replace('—', '', $value);
        // strip out hyphens
        return str_replace('-', '', $value);
    }
}
