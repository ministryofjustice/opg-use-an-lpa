<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class GenericGlobalVariableExtension extends AbstractExtension implements GlobalsInterface
{
    private string $application;

    public function __construct(string $application)
    {
        $this->application = $application;
    }

    public function getGlobals(): array
    {
        return [
            "application"   => $this->application,
            "currentLocale" => \Locale::getDefault()
        ];
    }
}
