<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Factory;

use Common\Entity\Person;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

class PersonDataFormatter
{
    private ObjectMapperUsingReflection $mapper;

    public function __construct()
    {
        $this->mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter: new KeyFormatterWithoutConversion(),
            ),
        );
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function __invoke(array $personJson)
    {
        return $this->mapper->hydrateObject(
            Person::class,
            $personJson,
        );
    }
}
