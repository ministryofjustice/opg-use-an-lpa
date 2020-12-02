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
        array_walk_recursive($data, [$this, 'removeHyphens']);

        return $data;
    }

    protected function removeHyphens(&$item, $key): void
    {
        if ($key === 'uId') {
            $item = str_replace('-', '', $item);
        }
    }
}
