<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\View\Twig\TokenParser\TransTokenParser;
use Laminas\I18n\Translator\TranslatorInterface;
use Twig\Extension\AbstractExtension;

class TranslationExtension extends AbstractExtension
{
    private ?TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers(): array
    {
        return [
            // {% trans %}Symfony could have made this easier!{% endtrans %}
            new TransTokenParser(),
        ];
    }

    public function trans(
        string $message,
        array $arguments = [],
        string $domain = null,
        string $locale = null,
        int $count = null
    ): string {
        if (null !== $count) {
            $arguments['%count%'] = $count;
        }

        return $this->interpolate($this->getTranslator()->translate($message, $domain, $locale), $arguments);
    }

    private function interpolate(string $message, array $arguments = []): string
    {
        return strtr($message, $arguments);
    }
}
