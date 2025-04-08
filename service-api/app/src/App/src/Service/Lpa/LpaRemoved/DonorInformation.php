<?php

namespace App\Service\Lpa\LpaRemoved;

use JsonSerializable;

class DonorInformation implements JsonSerializable
{
    public function __construct(
        public readonly LpaRemovedDonorInformationInterface $donorInformation,
        public readonly LpaRemovedInterface $lpaType,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'donor' => $this->donorInformation,
            'caseSubtype' => $this->lpaType,
        ];
    }
}