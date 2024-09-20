<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\Lpa;
use App\Entity\LpaStore\LpaStore;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

class LpaDataFormatter
{
    public function __construct()
    {
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function __invoke(array $lpa): Lpa
    {
        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter: new KeyFormatterWithoutConversion(),
            ),
        );

        return $mapper->hydrateObject(
            LpaStore::class,
            $lpa
        );
    }
}
