<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository\ActorCodesInterface;
use App\Service\Lpa\LpaService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CodeValidationStrategyFactory
{
    public const LEGACY_FLAG = 'use_legacy_codes_service';

    public function __invoke(ContainerInterface $container): CodeValidationStrategyInterface
    {
        $config = $container->get('config');

        if (
            isset($config['feature_flags'][self::LEGACY_FLAG])
            && $config['feature_flags'][self::LEGACY_FLAG] === 'true'
        ) {
            return new DynamoCodeValidationStrategy(
                $container->get(ActorCodesInterface::class),
                $container->get(LpaService::class),
                $container->get(LoggerInterface::class)
            );
        }

        // TODO here will go the return of the new codes service. Something like:
        return new CodesApiValidationStrategy();
    }
}
