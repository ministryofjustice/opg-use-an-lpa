<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\Response\LpaInterface;

class LpaFilterFactory
{
    public function __invoke(?LpaInterface $lpa): ?LpaInterface
    {
        return $lpa !== null ? new LpaFilter($lpa) : null;
    }
}