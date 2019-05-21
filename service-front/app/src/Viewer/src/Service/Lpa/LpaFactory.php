<?php

declare(strict_types=1);

namespace Viewer\Service\Lpa;

use Viewer\Entity\Lpa;

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