<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Factory;

use Common\Entity\CombinedLpa;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;

class LpaDataFormatter
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
    public function __invoke(array $lpaJson)
    {
        return $this->mapper->hydrateObject(
            CombinedLpa::class,
            $lpaJson,
        );
    }
}
