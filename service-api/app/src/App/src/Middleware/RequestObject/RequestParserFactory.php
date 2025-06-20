<?php

declare(strict_types=1);

namespace App\Middleware\RequestObject;

use Laminas\Form\Annotation\AttributeBuilder;
use Psr\Container\ContainerInterface;

class RequestParserFactory
{
    public function __invoke(ContainerInterface $container): RequestParser
    {
        return new RequestParser(
            $container->get('request_object_mapper'),
            $container->get(AttributeBuilder::class),
        );
    }
}
