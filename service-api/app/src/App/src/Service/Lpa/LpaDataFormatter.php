<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use EventSauce\ObjectHydrator\UnableToHydrateObject;
use App\Entity\Lpa;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;

class LpaDataFormatter
{
    public function __construct()
    {
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function __invoke(array $lpa)
    {

        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter: new KeyFormatterWithoutConversion(),
            ),
        );

        return $mapper->hydrateObject(
            Lpa::class,
            $lpa
        );
    }
}
