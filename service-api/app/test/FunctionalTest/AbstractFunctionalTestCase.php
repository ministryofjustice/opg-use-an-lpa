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

    public function setUp(): void
    {
        parent::setUp();

        $this->container         = require __DIR__ . '/../../config/container.php';
        $this->containerModifier = $this->container->get(PhpDiModifiableContainer::class);
        $this->application       = $this->container->get(Application::class);
    }
}