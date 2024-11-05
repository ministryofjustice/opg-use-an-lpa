<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface AddAccessForAllActorInterface
{
    public function getFirstname(): string;

    public function getMiddleNames(): string;

    public function getSurname(): string;

    public function getUid(): string;
}
