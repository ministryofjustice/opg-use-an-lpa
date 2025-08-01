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

        $value = strtoupper($value);
        // replaces other dashes with normal dash
        $value = preg_replace('/[–—]/u', '-', $value);

        if (preg_match('/^P[\- ]/', $value)) {
            // remove spaces or multiple hyphens to one
            $value = preg_replace('/[\- ]+/', '-', $value);
            return preg_replace('/^P\-*/', 'P-', $value);
        }

        // V codes - removes V- and hyphens (maintaining current behaviour)
        $value = preg_replace('/^V[\- ]*/', '', $value);

        return preg_replace('/[\- ]+/', '', $value);
    }
}
