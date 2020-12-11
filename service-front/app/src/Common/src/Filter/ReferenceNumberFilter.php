<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ReferenceNumberFilter extends AbstractFilter
{
    /**
     * @param string $referenceNo
     * @return string
     */
    public function filter($referenceNo): string
    {
        // strip out whitespace
        $referenceNo = str_replace(' ', '', $referenceNo);
        // strip out hyphens
        return str_replace('-', '', $referenceNo);
    }
}
