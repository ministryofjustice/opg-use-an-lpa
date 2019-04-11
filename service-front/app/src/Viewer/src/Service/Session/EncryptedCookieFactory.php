<?php

declare(strict_types=1);

namespace Viewer\Service\Session;

use Viewer\Service\Session\KeyManager\Manager;
use Psr\Container\ContainerInterface;

/**
 * Class EncryptedCookieFactory
 * @package App\Service\Session
 */
class EncryptedCookieFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new EncryptedCookie(
            $container->get(Manager::class)
        );
    }
}
