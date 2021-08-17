<?php

declare(strict_types=1);

namespace Actor\Validator;

use Laminas\Validator\AbstractValidator;

class OptionSelectedValidator extends AbstractValidator
{
    public const OPTION_MUST_BE_SELECTED = 'noOptionSelected';
    public const OPTION_MUST_BE_SELECTED_MESSAGE =
        'Either enter your phone number or check the box to say you cannot take calls';

    protected array $messageTemplates = [
        self::OPTION_MUST_BE_SELECTED => self::OPTION_MUST_BE_SELECTED_MESSAGE,
    ];

    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function isValid($value): bool
    {
        $valid = (!empty($value['telephone']) xor $value['no_phone'] === 'yes');
        if (!$valid) {
            $this->error(self::OPTION_MUST_BE_SELECTED);
        }
        return $valid;
    }
}
