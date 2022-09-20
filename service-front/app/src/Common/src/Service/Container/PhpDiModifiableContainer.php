<?php

declare(strict_types=1);

namespace Common\Service\Container;

use DI\Container;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Implementation of ModifiableContainerInterface for the PHP-DI dependency injection container.
 */
class PhpDiModifiableContainer implements ModifiableContainerInterface
{
    private Container|ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        if (!$container instanceof Container) {
            throw new InvalidArgumentException(
                'Container passed to ' . self::class . ' is not a PHP-DI container'
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
