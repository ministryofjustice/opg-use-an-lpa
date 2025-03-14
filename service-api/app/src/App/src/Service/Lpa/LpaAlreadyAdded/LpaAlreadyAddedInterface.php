<?php

declare(strict_types=1);

namespace App\Service\Lpa\LpaAlreadyAdded;

interface LpaAlreadyAddedInterface
{
    public function getDonor(): DonorInformationInterface;

    public function getCaseSubType(): string;
}
