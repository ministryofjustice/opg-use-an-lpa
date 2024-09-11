<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\LpaStore\LpaStore;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use EventSauce\ObjectHydrator\UnableToSerializeObject;

class LpaDataFormatter
{
    public function __construct()
    {
    }

    /**
     * @throws UnableToHydrateObject
     * @throws UnableToSerializeObject
     */
    public function __invoke(array $lpa)
    {
        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter:                new KeyFormatterWithoutConversion(),
            ),
        );

        $lpaObject = $mapper->hydrateObject(
            LpaStore::class,
            $lpa
        );

        return $mapper->serializeObject(
            $lpaObject
        );
    }
}
