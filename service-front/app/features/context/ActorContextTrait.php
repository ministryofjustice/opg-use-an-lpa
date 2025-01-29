<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeSuite;

trait ActorContextTrait
{
    #[BeforeSuite]
    public static function setupEnv(): void
    {
        // set the side of the application we're using
        putenv('CONTEXT=actor');

        putenv('AWS_ACCESS_KEY_ID=-');
        putenv('AWS_SECRET_ACCESS_KEY=-');
    }

    #[AfterSuite]
    public static function cleanupEnv(): void
    {
        putenv('CONTEXT=');

        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}
