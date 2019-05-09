<?php
declare(strict_types=1);

namespace Viewer;

use DI\Factory\RequestedEntry;
use Psr\Container\ContainerInterface;

/**
 * Creates an instance of the Requested Entry, and injects all the config into it.
 *
 * Used in the generation of autowired 'Config' instances.
 *
 * Class ConfigFactory
 * @package App
 */
class ConfigFactory
{
    public function __invoke(ContainerInterface $container, RequestedEntry $entityClass)
    {
        $class = $entityClass->getName();

        return new $class(
            $container->get('config')
        );
    }
}
