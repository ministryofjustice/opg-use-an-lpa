<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Service\I18n\ICUMessageFormatter;
use Common\View\Twig\TokenParser\TransTokenParser;
use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Translator\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    private ICUMessageFormatter $formatter;
    private ?TranslatorInterface $translator;


    public function __construct(TranslatorInterface $translator, ICUMessageFormatter $formatter)
    {
        $this->translator = $translator;
        $this->formatter = $formatter;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, 'trans']),
        ];
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
        string $domain = 'default',
        string $locale = null,
        int $count = null
    ): string {
        if (null !== $count) {
            $arguments['%count%'] = $count;
        }

        // ICU MessageFormatter needs to have the locale
        if ($this->getTranslator() instanceof Translator) {
            $locale ??= $this->getTranslator()->getLocale();
        } else {
            $locale ??= \Locale::getDefault();
        }

        return $this->formatter->format(
            $this->getTranslator()->translate($message, $domain, $locale),
            $locale,
            $arguments
        );
    }
}
