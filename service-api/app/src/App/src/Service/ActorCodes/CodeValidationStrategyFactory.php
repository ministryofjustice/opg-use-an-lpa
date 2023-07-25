<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\ApiGateway\ActorCodes as ActorCodesApi;
use App\DataAccess\Repository\ActorCodesInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\ActorCodes\Validation\{CodesApiValidationStrategy, DynamoCodeValidationStrategy};
use App\Service\Lpa\LpaService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CodeValidationStrategyFactory
{
    public function __invoke(ContainerInterface $container): CodeValidationStrategyInterface
    {
        return new CodesApiValidationStrategy(
            $container->get(ActorCodesApi::class),
            $container->get(LpaService::class),
            $container->get(LoggerInterface::class),
            $container->get(ResolveActor::class)
        );
    }
}
