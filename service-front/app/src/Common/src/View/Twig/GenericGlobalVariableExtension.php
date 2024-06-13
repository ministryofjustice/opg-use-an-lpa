<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Acpr\I18n\TranslatorInterface;
use Locale;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

use function str_replace;
use function strtolower;

class GenericGlobalVariableExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private string $application, private TranslatorInterface $translator)
    {
    }

    public function getGlobals(): array
    {
        return [
            'application'   => $this->application,
            'serviceName'   => $this->translator->translate(
                ($this->application === 'actor' ? 'Use' : 'View') . ' a lasting power of attorney',
                [],
                null
            ),
            'currentLocale' => strtolower(str_replace('_', '-', Locale::getDefault())),
        ];
    }
}
