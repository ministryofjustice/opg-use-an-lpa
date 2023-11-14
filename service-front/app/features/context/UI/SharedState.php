<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

final class SharedState
{
    private static ?SharedState $instance = null;

    /** @var string The base part of the URL, typically '/' but could be a language prefix i.e. '/cy' */
    public string $basePath = '';

    public static function getInstance(): SharedState
    {
        if (self::$instance === null) {
            self::$instance = new SharedState();
            self::$instance->reset();
        }

        return self::$instance;
    }

    public function reset(): void
    {
        $this->basePath = '';
    }
}
