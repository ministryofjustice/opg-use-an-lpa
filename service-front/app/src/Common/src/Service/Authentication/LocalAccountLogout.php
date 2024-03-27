<?php

declare(strict_types=1);

namespace Common\Service\Authentication;

use Mezzio\Authentication\UserInterface;

class LocalAccountLogout implements LogoutStrategy
{
    public const LOGOUT_REDIRECT_URL = 'https://www.gov.uk/done/use-lasting-power-of-attorney';

    public function logout(UserInterface $user): ?string
    {
        return self::LOGOUT_REDIRECT_URL;
    }
}