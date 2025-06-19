<?php

declare(strict_types=1);

namespace App\Middleware\RequestObject;

use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use Psr\Container\ContainerInterface;

class RequestObjectMapperFactory
{
    public function __invoke(ContainerInterface $container): ObjectMapper
    {
        return new ObjectMapperUsingReflection(
            $container->get('definition_provider_without_key_conversion')
        );
    }
}
