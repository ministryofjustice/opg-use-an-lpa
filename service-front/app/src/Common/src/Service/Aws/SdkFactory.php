<?php
declare(strict_types=1);

namespace Common\Service\Aws;

use Aws\Sdk;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Builds a configured instance of the AWS Sdk.
 *
 * Typically this shouldn't be used directly. See SecretsManagerFactory.php for an example of
 * how to create a client for a specific service.
 *
 * Class SdkFactory
 * @package Common\Service\Aws
 */
class SdkFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['aws'])) {
            throw new RuntimeException('Missing aws configuration');
        }

        return new Sdk($config['aws']);
    }
}
