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
        // Remove P- (hyphen) or P– (en dash) or P— (em dash) or V- from start of the code if present
        $code = preg_replace('/^((v|p)(–|—|-| ))?/i', '', strtoupper($code));

        return (new StripSpacesAndHyphens())->filter($code);
    }
}
