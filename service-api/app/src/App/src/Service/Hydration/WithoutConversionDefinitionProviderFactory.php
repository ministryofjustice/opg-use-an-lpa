<?php

declare(strict_types=1);

namespace App\Service\Hydration;

use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use Psr\Container\ContainerInterface;

class WithoutConversionDefinitionProviderFactory
{
    public function __invoke(ContainerInterface $container): DefinitionProvider
    {
        $keyFormatter = $container->get(KeyFormatterWithoutConversion::class);

        return new DefinitionProvider(
            keyFormatter: $keyFormatter,
        );
    }
}
