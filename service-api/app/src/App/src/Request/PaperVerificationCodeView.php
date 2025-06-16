<?php

declare(strict_types=1);

namespace App\Request;

use App\Value\CastToValueObject;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use DateTimeInterface;
use Laminas\Form\Annotation as Input;
use Laminas\Validator\Date;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;

class PaperVerificationCodeView implements InputFilteredRequest
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
        #[Input\Validator(NotEmpty::class, ['type' => NotEmpty::NULL])]
        #[Input\Validator(InArray::class, ['haystack' => [true, false], 'strict' => InArray::COMPARE_STRICT])]
        public readonly bool $sentToDonor,
        #[Input\Required]
        public readonly string $attorneyName,
        #[Input\Required]
        #[Input\Validator(Date::class, ['format' => 'Y-m-d'])]
        ##[CastToDateTimeImmutable('!Y-m-d')] # TODO Due to a bug in the hydrator we can't do this
        public readonly DateTimeInterface $dateOfBirth,
        #[Input\Required]
        public readonly int $noOfAttorneys,
        #[Input\Required]
        public readonly string $organisation,
    ) {
    }
}
