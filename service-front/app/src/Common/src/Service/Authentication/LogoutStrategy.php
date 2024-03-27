<?php

declare(strict_types=1);

namespace Common\Service\Authentication;

use Mezzio\Authentication\UserInterface;

/**
 * Provides a method for implementing class to affect the outcome
 * of a logout action. An implementing class can return a url to which the end user
 * can be redirected after the action is complete.
 */
interface LogoutStrategy
{
    /**
     * @param UserInterface $user The user object of the currently logged in user
     * @return string|null An optional url to which the user should be redirected
     */
    public function logout(UserInterface $user): ?string;
}
