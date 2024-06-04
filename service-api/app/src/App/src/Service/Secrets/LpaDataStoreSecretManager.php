<?php

declare(strict_types=1);

namespace App\Service\Secrets;

class LpaDataStoreSecretManager extends AbstractSecretManager
{

    public function getSecretName(): string
    {
        return 'lpa-data-store-secret';
    }
}