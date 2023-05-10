<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;

class InstructionsAndPreferencesImagesFactory
{
    public function __invoke(ContainerInterface $container): InstructionsAndPreferencesImages
    {
        $config = $container->get('config');

        if (!isset($config['codes_api']['endpoint'])) {
            throw new \Exception('Instructions and Preferences API Gateway endpoint is not set');
        }

        return new InstructionsAndPreferencesImages(
            $container->get(HttpClient::class),
            $container->get(RequestSigner::class),
            $config['codes_api']['endpoint'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
