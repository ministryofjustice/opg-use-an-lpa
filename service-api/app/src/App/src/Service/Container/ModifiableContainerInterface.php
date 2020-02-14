<?php

declare(strict_types=1);

namespace App\Service\Container;

/**
 * Interface ModifiableContainerInterface
 *
 * The PSR11 container standard does not allow any operations that alter the container. This interface allows
 * classes to accept a container instance that will let them alter configuration values set on the container.
 *
 * The interface is limited in scope to *only* allow the setting of configuration values within the container,
 * probably pulled from runtime calculations. Creation/alteration of more complicated items should be done as
 * a part of standard DI container instantiation.
 *
 * @package App\Service\Container
 */
interface ModifiableContainerInterface
{
    /**
     * Sets a configuration string into the container under a given key
     *
     * @param string $name The name of the configuration value to store
     * @param string $value The configuration value
     */
    public function setValue(string $name, string $value): void;
}
