<?php

declare(strict_types=1);

namespace BehatTest\Context;

trait ViewerContextTrait
{
    /**
     * @BeforeSuite
     */
    public static function setupEnv() {
        // set the side of the application we're using
        putenv('CONTEXT=viewer');

        putenv('AWS_ACCESS_KEY_ID=-');
        putenv('AWS_SECRET_ACCESS_KEY=-');
    }

    /**
     * @AfterSuite
     */
    public static function cleanupEnv()
    {
        putenv('CONTEXT=');

        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}