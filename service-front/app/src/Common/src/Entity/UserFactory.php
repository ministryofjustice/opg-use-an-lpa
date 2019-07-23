<?php

declare(strict_types=1);

namespace Common\Entity;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Authentication\UserInterface;

/**
 * Produces a callable factory capable of itself producing a UserInterface
 * instance; this approach is used to allow substituting alternative user
 * implementations without requiring extensions to existing repositories.
 *
 * @see \Zend\Expressive\Authentication\DefaultUserFactory
 */
class UserFactory
{
    public function __invoke(ContainerInterface $container) : callable
    {
        return function (string $identity, array $roles = [], array $details = []) : UserInterface {
            return new User($identity, $roles, $details);
        };
    }
}