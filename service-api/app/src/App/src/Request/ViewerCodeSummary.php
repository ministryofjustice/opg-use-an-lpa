<?php

declare(strict_types=1);

namespace App\Request;

use Laminas\Form\Annotation as Input;
use Laminas\Validator\Regex;

class ViewerCodeSummary implements InputFilteredRequest
{
    public function __construct(
        #[Input\Required]
        public readonly string $name,
        #[Input\Validator(Regex::class, ['pattern' => '/^[[:alnum:]]{12}$/'])]
        public readonly string $code,
    ) {
    }
}
