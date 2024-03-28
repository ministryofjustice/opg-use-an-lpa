<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

use App\Service\Authentication\AuthorisationClientManager;
use App\Service\Authentication\KeyPairManager\OneLoginUserInfoKeyPairManager;
use App\Service\Authentication\Token\OutOfBandCoreIdentityVerifierBuilder;
use App\Service\Authentication\UserInfoService;
use App\Service\Aws\SSMClientFactory;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use App\Service\Aws\SSMClient;

class SystemMessageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SystemMessage
    {
        return new SystemMessage(new SSMClient());
    }
}
