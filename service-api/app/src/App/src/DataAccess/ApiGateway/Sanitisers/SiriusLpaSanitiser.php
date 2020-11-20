<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway\Sanitisers;

use App\DataAccess\Repository\DataSanitiserStrategy;

class SiriusLpaSanitiser implements DataSanitiserStrategy
{
    /**
     * @inheritDoc
     */
    public function sanitise(array $data): array
    {
        return $data;
    }
}
