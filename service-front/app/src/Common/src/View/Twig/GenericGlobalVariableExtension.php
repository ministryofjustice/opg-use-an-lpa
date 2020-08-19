<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class GenericGlobalVariableExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'currentLocale' => \Locale::getDefault()
        ];
    }
}
