<?php

namespace Common\Filter;

use DateTime;
use DateTimeInterface;
use Laminas\Filter\AbstractFilter;

class ToDateTime extends AbstractFilter
{
    /**
     * Filter a datetime string by normalizing it to the filters specified format
     *
     * @param  string $value
     * @return DateTimeInterface
     */
    public function filter($value)
    {
        if (! is_string($value) && ! $value instanceof DateTime) {
            return $value;
        }

        $value = new DateTime($value);
        return $value;
    }
}
