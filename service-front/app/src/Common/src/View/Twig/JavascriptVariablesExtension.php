<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\Security\CSPNonce;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class JavascriptVariablesExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private CSPNonce $cspNonce, private string $googleAnalyticsID)
    {
    }

    public function getGlobals(): array
    {
        return [
            'cspNonce' => $this->cspNonce,
            'uaId'     => $this->googleAnalyticsID,
        ];
    }
}
