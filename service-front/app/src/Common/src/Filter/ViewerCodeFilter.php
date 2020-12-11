<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ViewerCodeFilter extends AbstractFilter
{
    /**
     * @param string $viewerCode
     * @return string
     */
    public function filter($viewerCode): string
    {
        //Remove V- from start of the code if present
        $viewerCode = preg_replace('/^(v(-| ))?/i', '', $viewerCode);
        // strip out whitespace
        $viewerCode = str_replace(' ', '', $viewerCode);
        // strip out hyphens
        return str_replace('-', '', $viewerCode);
    }
}
