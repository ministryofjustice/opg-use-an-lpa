<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class JavascriptVariablesExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private string $googleAnalyticsID)
    {
    }

    public function getGlobals(): array
    {
        return [
            'uaId' => $this->googleAnalyticsID,
        ];
    }
}
