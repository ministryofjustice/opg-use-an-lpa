<?php

declare(strict_types=1);

namespace App\Request;

use App\Value\CastToValueObject;
use App\Value\PaperVerificationCode;
use Laminas\Form\Annotation as Input;
use Laminas\Validator\Regex;

class PaperVerificationCodeUsable implements InputFilteredRequest
{
    public function __construct(
        #[Input\Required]
        public readonly string $name,
        #[Input\Required]
        #[Input\Validator(Regex::class, ['pattern' => '/^P(-[[:alnum:]]{4}){3}-[[:alnum:]]{2}$/'])]
        #[CastToValueObject(PaperVerificationCode::class)]
        public readonly PaperVerificationCode $code,
    ) {
    }
}
