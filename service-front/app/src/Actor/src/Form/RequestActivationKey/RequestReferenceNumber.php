<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Filter\StripSpacesAndHyphens;
use Common\Form\AbstractForm;
use Common\Validator\{DammCheck, LuhnCheck, MerisReferenceCheckValidator, SiriusReferenceStartsWithCheck};
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{Digits, NotEmpty, StringLength, Regex};
use Mezzio\Csrf\CsrfGuardInterface;

class RequestReferenceNumber extends AbstractForm implements InputFilterProviderInterface
{
    public const string FORM_NAME = 'request_activation_key_reference_number';

    public function __construct(
        CsrfGuardInterface $csrfGuard,
        private bool $merisEntryEnabled,
        private bool $paperVerification,
    ) {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'opg_reference_number',
            'type' => 'Text',
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        $validators          = [
            [
                'name'                   => NotEmpty::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'message' => 'Enter the LPA reference number',
                ],
            ],
            [
                'name'                   => Digits::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'message'
                        => 'Enter the 12 numbers of the LPA reference number. ' .
                        'Do not include letters or other characters',
                ],
            ],
            [
                'name'                   => SiriusReferenceStartsWithCheck::class,
                'break_chain_on_failure' => true,
            ],
            [
                'name' => LuhnCheck::class,
            ],
        ];
        $stringLength        = [
            'name'                   => StringLength::class,
            'break_chain_on_failure' => true,
            'options'                => [
                'encoding' => 'UTF-8',
                'min'      => 12,
                'max'      => 12,
                'messages' => [
                    StringLength::TOO_LONG  => 'The LPA reference number you entered is too long',
                    StringLength::TOO_SHORT => 'The LPA reference number you entered is too short',
                ],
            ],
        ];
        $merisReferenceCheck = [
            'name' => MerisReferenceCheckValidator::class,
        ];

        if ($this->merisEntryEnabled) {
            array_push($validators, $merisReferenceCheck);
        } else {
            array_push($validators, $stringLength);
        }

        if ($this->paperVerification) {
            $validators = [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'message' => 'Enter the LPA reference number',
                    ],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern' => $this->merisEntryEnabled
                            ? '/^(7\d{11}|(M|m)\d{12}|(2|3)\d{6})$/'
                            : '/^(7\d{11}|(M|m)\d{12})$/i',
                        'message' => 'Enter the LPA reference number in the correct format',
                    ],
                ],
                [
                    'name' => LuhnCheck::class,
                ],
                [
                    'name' => DammCheck::class,
                ],
            ];
        }

        return [
            'opg_reference_number' => [
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => StripSpacesAndHyphens::class],
                ],
                'validators' => $validators,
            ],
        ];
    }
}
