<?php

declare(strict_types=1);

namespace Common\Filter;

use Exception;
use Laminas\Filter\FilterInterface;

class ActorViewerCodeFilter implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function filter($value): string
    {
        if (!is_string($value)) {
            throw new Exception('Invalid filter value - expecting string');
        }

        // Remove C- (hyphen) or C– (en dash) or C— (em dash) or V- from start of the code if present
        $value = preg_replace('/^((v|c)(–|—|-| ))?/i', '', strtoupper($value));

        return (new StripSpacesAndHyphens())->filter($value);
    }
}
