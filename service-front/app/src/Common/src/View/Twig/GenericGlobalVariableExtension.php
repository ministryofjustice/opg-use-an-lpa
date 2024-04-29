<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Locale;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

use function str_replace;
use function strtolower;

class GenericGlobalVariableExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private string $application)
    {
    }

    public function getGlobals(): array
    {
        return [
            'application'   => $this->application,
            'serviceName'   => ($this->application === 'actor' ? 'Use' : 'View') . ' a lasting power of attorney',
            'currentLocale' => strtolower(str_replace('_', '-', Locale::getDefault())),
        ];
    }
}
