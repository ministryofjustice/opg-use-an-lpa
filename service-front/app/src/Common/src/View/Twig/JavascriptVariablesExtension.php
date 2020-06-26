<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Class JavascriptvariablesExtension
 * @package Common\View\Twig
 */
class JavascriptVariablesExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var string
     */
    private string $googleAnalyticsID;

    /**
     * JavascriptVariablesExtension constructor.
     * @param string $googleAnalyticsID
     */
    public function __construct(string $googleAnalyticsID)
    {
        $this->googleAnalyticsID = $googleAnalyticsID;
    }

    public function getGlobals(): array
    {
        return [
            'uaId' => $this->googleAnalyticsID
        ];
    }
}
