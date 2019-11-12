<?php
/**
 * Created by PhpStorm.
 * User: seemamenon
 * Date: 11/11/2019
 * Time: 14:57
 */

namespace Common\Form\Fieldset;


use Zend\Filter\AbstractFilter;

class DatePrefixFilter extends AbstractFilter
{

    /**
     * Defined by Zend\Filter\FilterInterface
     *
     * Returns the string $value with zero prefixed at the beginning of the value
     *
     * @param  array $value
     * @return string
     */
    public function filter($value)
    {
        if ($value != null && ($value['day'] < 9 || $value['month'] < 9)) {
            return $this->formatWithLeadingZero($value);
        }
        return $value;
    }

    /**
     *
     * @param array $value
     * @param string $formattedDate
     * @return string
     */
    protected function formatWithLeadingZero($value)
    {
        $value['day'] = str_pad($value['day'], 2, 0, STR_PAD_LEFT);
        $value['month'] = str_pad($value['month'], 2, 0, STR_PAD_LEFT);

        return $value;
    }
}

