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
    public function __invoke(array $lpa): Lpa
    {
        return $this->hydrateObject($lpa);
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(array $lpa): Lpa
    {
        return $this->mapper->hydrateObject(
            $this->getHydrationClass($lpa),
            $lpa,
        );
    }

    /**
     * @param array $lpa
     * @return string
     * @psalm-return class-string<LpaStore|SiriusLpa>
     */
    private function getHydrationClass(array $lpa): string
    {
        return isset($lpa['uid']) && str_starts_with($lpa['uid'], 'M-')
            ? LpaStore::class
            : SiriusLpa::class;
    }
}
