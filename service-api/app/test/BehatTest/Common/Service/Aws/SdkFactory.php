<?php

declare(strict_types=1);

namespace BehatTest\Common\Service\Aws;

use Aws\MockHandler;
use Aws\Sdk;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Builds a configured instance of the AWS Sdk for testing
 *
 * Utilises an Aws MockHandler to allow us to stub requests to the Aws platform.
 *
 * Class SdkFactory
 * @package BehatTest\Common\Service\Aws
 */
class SdkFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['aws'])) {
            throw new RuntimeException('Missing aws configuration');
        }

        $handler = $container->get(MockHandler::class);

        return new Sdk(
            array_merge(
                $config['aws'],
                ['handler' => $handler]
            )
        );
    }
}
