<?php

declare(strict_types=1);

namespace Common\Form\Fieldset;

use Laminas\Filter\AbstractFilter;

class DateTrimFilter extends AbstractFilter
{

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns the array $value with  the values trimmed for day month and year
     *
     * @param  array $value
     * @return array
     */
    public function filter($value): array
    {
        $value['day'] = trim($value['day']);
        $value['month'] = trim($value['month']);
        $value['year'] = trim($value['year']);
        return $value;
    }
}
