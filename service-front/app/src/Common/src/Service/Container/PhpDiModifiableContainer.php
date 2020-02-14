<?php

declare(strict_types=1);

namespace Common\Service\Container;

use DI\Container;
use Psr\Container\ContainerInterface;

/**
 * Class PhpDiModifiableContainer
 *
 * Implementation of ModifiableContainerInterface for the PHP-DI dependency injection container.
 *
 * @package Common\Service\Container
 */
class PhpDiModifiableContainer implements ModifiableContainerInterface
{
    /**
     * @var Container|ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        if (!$container instanceof Container) {
            throw new \InvalidArgumentException(
                'Container passed to ' . __CLASS__ . ' is not a PHP-DI container'
            );
        }

        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $name, string $value): void
    {
        $this->container->set($name, $value);
    }
}