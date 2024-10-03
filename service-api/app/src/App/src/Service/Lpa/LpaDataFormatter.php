<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\LpaStore\LpaStore;
use App\Entity\Sirius\SiriusLpa;
use App\Service\Features\FeatureEnabled;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use RuntimeException;

class LpaDataFormatter
{
    private ObjectMapperUsingReflection $mapper;

    public function __construct(
        private FeatureEnabled $featureEnabled,
    ) {
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
    public function __invoke(array $lpa)
    {
        $lpaObject = $this->hydrateObject($lpa);

        if (!($this->featureEnabled)('support_datastore_lpas')) {
            return $this->mapper->serializeObject($lpaObject);
        }

        return $lpaObject;
    }

    /**
     * @throws UnableToHydrateObject
     */
    public function hydrateObject(array $lpa)
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
