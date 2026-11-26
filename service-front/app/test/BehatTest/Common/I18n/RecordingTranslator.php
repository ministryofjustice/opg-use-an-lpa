<?php

declare(strict_types=1);

namespace BehatTest\Common\I18n;

use Acpr\I18n\TranslatorInterface;
use Gettext\TranslatorInterface as GettextTranslatorInterface;

use function preg_match;

/**
 * Records translation keys that gettext could not find in the active locale's catalog.
 *
 * Gettext returns the original message when no translation exists, so a key-shaped message returned unchanged
 * is a miss. English message IDs are ignored as they legitimately translate to themselves.
 */
final class RecordingTranslator implements TranslatorInterface
{
    private const string KEY_PATTERN = '/^[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)+$/';

    /** @var array<string, true> */
    private array $missing = [];

    public function __construct(private readonly TranslatorInterface $inner)
    {
    }

    public function inner(): TranslatorInterface
    {
        return $this->inner;
    }

    public function setLocale(string $locale): void
    {
        $this->inner->setLocale($locale);
    }

    public function getTranslator(): GettextTranslatorInterface
    {
        return $this->inner->getTranslator();
    }

    public function translate(
        string $original,
        array $replacements = [],
        ?string $domain = null,
        ?string $context = null,
        ?string $plural = null,
        ?int $count = null,
    ): string {
        $translated = $this->inner->translate($original, $replacements, $domain, $context, $plural, $count);

        // Keys contain no replacement tokens, so an untranslated key is returned exactly as given
        foreach ($count === null ? [$original] : [$original, $plural] as $key) {
            if ($key !== null && $translated === $key && preg_match(self::KEY_PATTERN, $key) === 1) {
                $this->missing[$context === null ? $key : $context . '|' . $key] = true;
            }
        }

        return $translated;
    }

    /**
     * @return list<string> Missing keys recorded since the last call, prefixed with their context where present
     */
    public function flushMissing(): array
    {
        $missing       = array_keys($this->missing);
        $this->missing = [];

        return $missing;
    }
}
