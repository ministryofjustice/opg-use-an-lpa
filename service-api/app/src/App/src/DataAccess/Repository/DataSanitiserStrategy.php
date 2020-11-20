<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface DataSanitiserStrategy
{
    /**
     * @param array $data An associative array of data to sanitise
     * @return array The array with clean data
     */
    public function sanitise(array $data): array;
}
