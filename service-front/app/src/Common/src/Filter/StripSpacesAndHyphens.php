<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class StripSpacesAndHyphens extends AbstractFilter
{
    /**
     * @param string $value
     * @return string
     */
    public function filter($value): string
    {
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
