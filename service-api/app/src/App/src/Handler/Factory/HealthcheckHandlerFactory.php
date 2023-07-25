<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\ActorUsersInterface;
use App\Handler\HealthcheckHandler;
use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class HealthcheckHandlerFactory
{
    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): HealthcheckHandler
    {
        $config = $container->get('config');

        return new HealthcheckHandler(
            $config['version'],
            $container->get(ActorUsersInterface::class),
            $container->get(HttpClient::class),
            $container->get(RequestSigner::class),
            $config['sirius_api']['endpoint'],
            $config['codes_api']['endpoint'],
        );
    }
}

