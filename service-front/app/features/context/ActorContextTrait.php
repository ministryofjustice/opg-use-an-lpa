<?php

declare(strict_types=1);

namespace BehatTest\Context;

trait ActorContextTrait
{
    /**
     * @BeforeSuite
     */
    public static function setupEnv() {
        // set the side of the application we're using
        putenv('CONTEXT=actor');

        putenv('IDENTIFY_HASH_SALT=a_random_salt_value');

        putenv('AWS_ACCESS_KEY_ID=-');
        putenv('AWS_SECRET_ACCESS_KEY=-');
    }

    /**
     * @AfterSuite
     */
    public static function cleanupEnv()
    {
        putenv('CONTEXT=');

        putenv('IDENTIFY_HASH_SALT=');

        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}