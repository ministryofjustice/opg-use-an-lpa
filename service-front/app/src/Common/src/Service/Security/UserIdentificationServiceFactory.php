<?php

declare(strict_types=1);

namespace Common\Service\Security;

class UserIdentificationServiceFactory
{
    public function __invoke(): UserIdentificationService
    {
        return new UserIdentificationService('salt_to_come_from_config');
    }
}