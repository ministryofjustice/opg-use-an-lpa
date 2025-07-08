<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ShareCodeFilter extends AbstractFilter
{
    /**
     * @param string $code
     * @return string
     */
    public function filter($code): string
    {
        $code = strtoupper($code);
        // replaces other dashes with normal dash
        $code = preg_replace('/[–—]/u', '-', $code);

        if (preg_match('/^P[\- ]/', $code)) {
            // remove spaces or multiple hyphens to one
            $code = preg_replace('/[\- ]+/', '-', $code);
            return preg_replace('/^P\-*/', 'P-', $code);
        }

        // V codes - removes V- and hyphens (maintaining current behaviour)
        $code = preg_replace('/^V[\- ]*/', '', $code);

        return preg_replace('/[\- ]+/', '', $code);
    }
}
