<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\Lpa;

interface LpaFactory
{
    /**
     * Creates a Lpa from the supplied data array.
     *
     * @param array $data
     * @return Lpa
     */
    public function createLpaFromData(array $data) : Lpa;
}