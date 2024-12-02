<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Factory;

use Common\Entity\LpaStore\LpaStore;
use Common\Entity\Sirius\SiriusLpa;
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
    public function __invoke(array $lpa)
    {
        return $this->hydrateObject($lpa);
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(array $lpa): object
    {
        $className = $this->getHydrationClass($lpa);

        return $this->mapper->hydrateObject(
            $className,
            $lpa
        );
    }

    private function getHydrationClass(array $lpa): string
    {
        return isset($lpa['uid']) && str_starts_with($lpa['uid'], 'M-')
            ? LpaStore::class
            : SiriusLpa::class;
    }
}
