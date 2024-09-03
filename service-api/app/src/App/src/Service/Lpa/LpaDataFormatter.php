<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\Casters\DateToStringSerializer;
use App\Entity\LpaStore\LpaStore;
use App\Entity\Sirius\SiriusLpa;
use DateTimeInterface;
use EventSauce\ObjectHydrator\DefaultSerializerRepository;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use DateTimeImmutable;
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
        $defaultSerializerRepository = new DefaultSerializerRepository([
            DateTimeImmutable::class => [
                DateToStringSerializer::class,
            ],
            DateTimeInterface::class => [
                DateToStringSerializer::class,
            ],
        ]);
        $defaultSerializerRepository->registerDefaultSerializer(
            DateTimeImmutable::class,
            DateToStringSerializer::class,
            []
        );

        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter:                new KeyFormatterWithoutConversion(),
                defaultSerializerRepository: $defaultSerializerRepository
            ),
        );

        $lpaObject = $mapper->hydrateObject(
            SiriusLpa::class,
            $lpa
        );

        return $mapper->serializeObject(
            $lpaObject
        );
    }
}
