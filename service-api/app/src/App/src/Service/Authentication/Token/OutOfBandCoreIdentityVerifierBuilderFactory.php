<?php

declare(strict_types=1);

namespace App\Service\Authentication\Token;

use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class OutOfBandCoreIdentityVerifierBuilderFactory
{
    public function __invoke(ContainerInterface $container): OutOfBandCoreIdentityVerifierBuilder
    {
        $config = $container->get('config');

        if (! array_key_exists('one_login', $config)) {
            throw new RuntimeException('One Login configuration not present');
        }

        return new OutOfBandCoreIdentityVerifierBuilder(
            $config['one_login']['identity_issuer'],
            $container->get(ClockInterface::class),
        );
    }
}
