<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Stringable;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * Allows translators to remove optional content, such as form field hints, by translating its key to a marker value
 */
class OptionalContentExtension extends AbstractExtension
{
    public const string NO_HINT = '[no-hint]';

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('no_hint', [$this, 'isNoHint']),
        ];
    }

    public function isNoHint(mixed $value): bool
    {
        return (is_string($value) || $value instanceof Stringable) && trim((string) $value) === self::NO_HINT;
    }
}
