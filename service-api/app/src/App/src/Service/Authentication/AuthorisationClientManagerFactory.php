<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Service\Authentication\KeyPairManager\OneLoginIdentityKeyPairManager;
use App\Service\Cache\CacheFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use RuntimeException;

class AuthorisationClientManagerFactory
{
    public function __invoke(ContainerInterface $container): AuthorisationClientManager
    {
        $config = $container->get('config');

        if (! array_key_exists('one_login', $config)) {
            throw new RuntimeException('One Login configuration not present');
        }

        return new AuthorisationClientManager(
            $config['one_login']['client_id'],
            $config['one_login']['discovery_url'],
            $container->get(JWKFactory::class),
            $container->get(OneLoginIdentityKeyPairManager::class),
            $container->get(IssuerBuilder::class),
            $container->get(CacheFactory::class),
            $container->get(PsrClientInterface::class),
        );
    }
}
