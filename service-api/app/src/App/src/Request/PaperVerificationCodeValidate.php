<?php

declare(strict_types=1);

namespace App\Request;

use App\Value\CastToValueObject;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use DateTimeInterface;
use Laminas\Form\Annotation as Input;
use Laminas\Validator\Regex;

class PaperVerificationCodeValidate implements InputFilteredRequest
{
    public function __construct(
        #[Input\Required]
        public readonly string $name,
        #[Input\Validator(Regex::class, ['pattern' => '/^P(-[[:alnum:]]{4}){3}-[[:alnum:]]{2}$/'])]
        #[CastToValueObject(PaperVerificationCode::class)]
        public readonly PaperVerificationCode $code,
        #[Input\Required]
        #[CastToValueObject(LpaUid::class)]
        public readonly LpaUid $lpaUid,
        #[Input\Required]
        public readonly bool $sentToDonor,
        #[Input\Required]
        public readonly string $attorneyName,
        #[Input\Required]
        public readonly DateTimeInterface $dateOfBirth,
        #[Input\Required]
        public readonly int $noOfAttorneys,
    ) {
    }
}
