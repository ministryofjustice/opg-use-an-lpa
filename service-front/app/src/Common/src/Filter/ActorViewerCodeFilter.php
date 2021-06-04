<?php

declare(strict_types=1);

namespace Common\Filter;

use Laminas\Filter\AbstractFilter;

class ActorViewerCodeFilter extends AbstractFilter
{
    /**
     * @param string $code
     * @return string
     */
    public function filter($code): string
    {
        // Remove C- or V- from start of the code if present
        $code = preg_replace('/^((v|c)(–|—|-| ))?/i', '', strtoupper($code));

        return (new StripSpacesAndHyphens())->filter($code);
    }
}
