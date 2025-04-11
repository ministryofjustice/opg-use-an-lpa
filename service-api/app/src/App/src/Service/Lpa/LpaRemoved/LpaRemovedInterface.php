<?php

declare(strict_types=1);

namespace App\Service\Lpa\LpaRemoved;

interface LpaRemovedInterface
{
    public function getDonor(): LpaRemovedDonorInformationInterface;

    public function getCaseSubType(): string;
}
