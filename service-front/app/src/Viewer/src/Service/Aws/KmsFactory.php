<?php
declare(strict_types=1);

namespace Viewer\Service\Aws;

use Psr\Container\ContainerInterface;

/**
 * Builds a configured instance of the AWS KMS Client
 *
 * Class KmsFactory
 * @package Viewer\Service\Aws
 */
class KmsFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $container->get(\Aws\Sdk::class)->createKms();
    }
}
