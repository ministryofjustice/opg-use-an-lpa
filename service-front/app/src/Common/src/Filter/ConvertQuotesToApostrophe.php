<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ConvertQuotesToApostrophe extends AbstractFilter
{
    /**
     * @param  string $name
     * @return string
     */
    public function filter($name): string
    {
        return  str_replace(['‘', '’'], "'", $name);
    }
}
