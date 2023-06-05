<?php

declare(strict_types=1);

namespace BehatTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\DataAccess\ApiGateway\RequestSigner;
use App\Service\Log\RequestTracing;
use DI\NotFoundException;
use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;

class PactInstructionsAndPreferencesImagesFactory
{
    /**
     * @param ContainerInterface $container
     * @return InstructionsAndPreferencesImages
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['iap_images_api']['endpoint'])) {
            throw new NotFoundException('Instructions and preferences images API Gateway endpoint is not set');
        }

        $apiHost = parse_url($config['iap_images_api']['endpoint'], PHP_URL_HOST);

        return new InstructionsAndPreferencesImages(
            new HttpClient(),
            $container->get(RequestSigner::class),
            $apiHost,
            $container->get(RequestTracing::TRACE_PARAMETER_NAME),
        );
    }
}
