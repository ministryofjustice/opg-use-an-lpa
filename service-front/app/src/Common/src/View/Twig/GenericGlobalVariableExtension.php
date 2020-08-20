<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Class GenericGlobalVariableExtension
 * @package Common\View\Twig
 */
class GenericGlobalVariableExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var string
     */
    private string $application;

    /**
     * GenericGlobalVariableExtension constructor.
     * @param string $application
     */
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
