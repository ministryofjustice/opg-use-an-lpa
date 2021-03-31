<?php

declare(strict_types=1);

namespace Common\Form\Fieldset;

use Laminas\Filter\AbstractFilter;

class DatePrefixFilter extends AbstractFilter
{

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns the array $value with zero prefixed at the beginning of the values for day and month
     *
     * @param  array $value
     * @return array
     */
    public function filter($value): array
    {
        if (
            $value != null && !empty($value['day']) && !empty($value['month'])
            && ($value['day'] <= 9 || $value['month'] <= 9)
        ) {
            return $this->formatWithLeadingZero($value);
        }
        return $value;
    }

    /**
     *
     * @param array $value
     * @param array $formattedDate
     * @return array
     */
    protected function formatWithLeadingZero($value): array
    {
        $value['day'] = str_pad($value['day'], 2, '0', STR_PAD_LEFT);
        $value['month'] = str_pad($value['month'], 2, '0', STR_PAD_LEFT);

        return $value;
    }
}
