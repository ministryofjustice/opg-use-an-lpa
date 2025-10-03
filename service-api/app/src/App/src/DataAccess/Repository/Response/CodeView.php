<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Entity\Lpa;

final class CodeView
{
    /**
     * @param Lpa $lpa
     */
    public function __construct(private Lpa $lpa)
    {
    }

    public function getData(): Lpa
    {
        return $this->lpa;
    }
}
