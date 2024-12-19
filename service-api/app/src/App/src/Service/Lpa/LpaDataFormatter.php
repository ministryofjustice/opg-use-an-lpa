<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\Lpa;
use App\Entity\LpaStore\LpaStore;
use App\Entity\Sirius\SiriusLpa;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use RuntimeException;

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
     * @throws RuntimeException
     */
    public function __invoke(array $lpa): Lpa
    {
        $lpaObject = $this->hydrateObject($lpa);

        return $lpaObject;
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

    public function serializeObject(object $lpa): mixed
    {
        return $this->mapper->serializeObject($lpa);
    }

    private function getHydrationClass(array $lpa): string
    {
        return isset($lpa['uid']) && str_starts_with($lpa['uid'], 'M-')
            ? LpaStore::class
            : SiriusLpa::class;
    }
}
