<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\ApiGateway\ActorCodes as ActorCodesApi;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\ActorCodes\Validation\CodesApiValidationStrategy;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CodeValidationStrategyFactory
{
    public function __invoke(ContainerInterface $container): CodeValidationStrategyInterface
    {
        return new CodesApiValidationStrategy(
            $container->get(ActorCodesApi::class),
            $container->get(LpaManagerInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(ResolveActor::class)
        );
    }
}
