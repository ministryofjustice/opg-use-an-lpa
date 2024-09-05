<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

class Person
{
    public function __construct(
        public readonly ?string $uId,
        public readonly ?string $name,
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        public readonly ?string $country,
        public readonly ?string $county,
        public readonly ?string $postcode,
        public readonly ?string $town,
        public readonly ?string $type,
        public readonly ?DateTimeImmutable $dob,
        public readonly ?string $email,
        public readonly ?string $firstname,
        public readonly ?string $firstnames,
        public readonly ?string $surname,
        public readonly ?string $otherNames,
        public readonly ?string $systemStatus,
    ) {
    }
}
