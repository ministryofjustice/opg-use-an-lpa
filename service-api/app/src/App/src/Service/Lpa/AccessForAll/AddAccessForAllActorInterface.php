<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

interface AddAccessForAllActorInterface
{
    public function getFirstname(): string;

    public function getMiddleNames(): string;

    public function getSurname(): string;

    public function getUid(): string;
}
