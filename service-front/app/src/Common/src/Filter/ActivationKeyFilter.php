<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ActivationKeyFilter extends AbstractFilter
{
    /**
     * @param string $activationKey
     * @return string
     */
    public function filter($activationKey): string
    {
        //Remove C- from start of the code if present
        $activationKey = preg_replace('/^(c(-| ))?/i', '', $activationKey);
        // strip out whitespace
        $activationKey = str_replace(' ', '', $activationKey);
        // strip out hyphens
        return str_replace('-', '', $activationKey);
    }
}
