<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Service\Authentication\KeyPairManager\OneLoginUserInfoKeyPairManager;
use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class UserInfoServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new UserInfoService(
            $container->get(UserInfoServiceBuilder::class),
            $container->get(AuthorisationClientManager::class),
            $container->get(OneLoginUserInfoKeyPairManager::class), // defined as KeyPairManagerInterface in class
            $container->get(JWKFactory::class),
            $container->get(OutOfBandCoreIdentityVerifierBuilder::class),
        );
    }
}