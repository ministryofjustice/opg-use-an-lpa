<?php

declare(strict_types=1);

namespace BehatTest\Context;

trait SetupEnvTrait
{
    /**
     * @BeforeSuite
     */
    public static function setupEnv() {
        putenv('AWS_ACCESS_KEY_ID=-');
        putenv('AWS_SECRET_ACCESS_KEY=-');
    }

    /**
     * @AfterSuite
     */
    public static function cleanupEnv()
    {
        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}