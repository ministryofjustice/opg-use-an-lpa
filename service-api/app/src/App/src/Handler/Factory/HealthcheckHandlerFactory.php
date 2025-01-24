<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\ActorUsersInterface;
use App\Handler\HealthcheckHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HealthcheckHandlerFactory
{
    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): HealthcheckHandler
    {
        $config = $container->get('config');

        return new HealthcheckHandler(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            $container->get(ActorUsersInterface::class),
            $config['version'],
            $config['sirius_api']['endpoint'],
            $config['lpa_data_store_api']['endpoint'],
            $config['codes_api']['endpoint'],
            $config['iap_images_api']['endpoint']
        );
    }
}
