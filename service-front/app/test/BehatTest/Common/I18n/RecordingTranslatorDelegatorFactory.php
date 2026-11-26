<?php

declare(strict_types=1);

namespace BehatTest\Common\I18n;

use Acpr\I18n\TranslatorInterface;
use Psr\Container\ContainerInterface;

final class RecordingTranslatorDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): TranslatorInterface
    {
        return new RecordingTranslator($callback());
    }
}
