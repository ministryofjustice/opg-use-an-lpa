<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeSuite;

trait SetupEnv
{
    #[BeforeSuite]
    public static function setupEnv(): void
    {
        putenv('AWS_ACCESS_KEY_ID=-');
        putenv('AWS_SECRET_ACCESS_KEY=-');
    }

    #[AfterSuite]
    public static function cleanupEnv(): void
    {
        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}
