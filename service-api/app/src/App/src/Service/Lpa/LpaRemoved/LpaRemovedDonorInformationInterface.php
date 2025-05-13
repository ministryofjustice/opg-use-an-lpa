<?php

declare(strict_types=1);

namespace App\Service\Lpa\LpaRemoved;

interface LpaRemovedDonorInformationInterface
{
    public function getUid(): string;

    public function getFirstnames(): string;

    public function getMiddleNames(): string;

    public function getSurname(): string;
}
