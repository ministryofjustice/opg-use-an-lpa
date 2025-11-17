<?php

declare(strict_types=1);

namespace FunctionalTest;

use App\Service\Container\PhpDiModifiableContainer;
use Mezzio\Application;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AbstractFunctionalTestCase extends TestCase
{
    protected Application $application;

    protected ContainerInterface $container;

    protected PhpDiModifiableContainer $containerModifier;

    protected function setUp(): void
    {
        parent::setUp();

        // Keys from the documentation
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        $this->container         = require __DIR__ . '/../../config/container.php';
        $this->containerModifier = $this->container->get(PhpDiModifiableContainer::class);
        $this->application       = $this->container->get(Application::class);
    }

    protected function tearDown(): void
    {
        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
    }
}
