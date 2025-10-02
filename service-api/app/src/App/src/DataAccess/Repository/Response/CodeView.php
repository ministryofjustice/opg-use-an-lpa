<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Entity\Lpa as CombinedFormatLpa;
final class CodeView
{
    /**
     * @param array|CombinedFormatLpa|null $lpa
     */
    public function __construct(private array|CombinedFormatLpa|null $lpa)
    {
    }

    public function getData(): array|CombinedFormatLpa|null
    {
        return $this->lpa;
    }
}
