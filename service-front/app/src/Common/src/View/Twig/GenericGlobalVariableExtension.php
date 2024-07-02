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
        $useServiceName = $this->translator->translate('Use a lasting power of attorney', []);
        $viewServiceName = $this->translator->translate('View a lasting power of attorney', []);

        return [
            'application'   => $this->application,
            'serviceName'   => $this->application === 'actor' ? $useServiceName : $viewServiceName,
            'currentLocale' => strtolower(str_replace('_', '-', Locale::getDefault())),
        ];
    }
}
