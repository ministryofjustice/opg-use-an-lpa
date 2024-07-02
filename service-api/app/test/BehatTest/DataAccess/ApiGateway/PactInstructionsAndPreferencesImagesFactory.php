<?php

declare(strict_types=1);

namespace BehatTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\Service\Log\RequestTracing;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PactInstructionsAndPreferencesImagesFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InstructionsAndPreferencesImagesInterface
    {
        $config = $container->get('config');

        if (!isset($config['iap_images_api']['endpoint'])) {
            throw new NotFoundException('Instructions and preferences images API Gateway endpoint is not set');
        }

        return new InstructionsAndPreferencesImages(
            $container->get(ClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RequestSignerFactory::class),
            parse_url($config['iap_images_api']['endpoint'], PHP_URL_HOST),
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
        );
    }
}
