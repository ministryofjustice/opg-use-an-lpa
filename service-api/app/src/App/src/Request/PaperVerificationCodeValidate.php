<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\Casters\CastToDateTimeImmutable;
use App\Value\CastToValueObject;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use DateTimeInterface;
use Laminas\Form\Annotation as Input;
use Laminas\Validator\InArray;
use Laminas\Validator\Regex;
use Laminas\Validator\Date;

class PaperVerificationCodeValidate implements InputFilteredRequest
{
    public function __construct(
        #[Input\Required]
        public readonly string $name,
        #[Input\Required]
        #[Input\Validator(Regex::class, ['pattern' => '/^P(-[[:alnum:]]{4}){3}-[[:alnum:]]{2}$/'])]
        #[CastToValueObject(PaperVerificationCode::class)]
        public readonly PaperVerificationCode $code,
        #[Input\Required]
        #[Input\Validator(Regex::class, ['pattern' => '/^M(-[[:digit:]]{4}){3}$/'])]
        #[CastToValueObject(LpaUid::class)]
        public readonly LpaUid $lpaUid,
        #[Input\Required]
        #[Input\AllowEmpty]
        #[Input\Validator(InArray::class, ['haystack' => [true, false], 'strict' => InArray::COMPARE_STRICT])]
        public readonly bool $sentToDonor,
        #[Input\Required]
        public readonly string $attorneyName,
        #[Input\Required]
        #[Input\Validator(Date::class, ['format' => 'Y-m-d'])]
        #[CastToDateTimeImmutable('!Y-m-d')]
        public readonly DateTimeInterface $dateOfBirth,
        #[Input\Required]
        public readonly int $noOfAttorneys,
    ) {
    }
}
