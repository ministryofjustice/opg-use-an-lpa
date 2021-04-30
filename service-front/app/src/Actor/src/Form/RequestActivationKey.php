<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Filter\StripSpacesAndHyphens;
use Common\Form\AbstractForm;
use Common\Form\Fieldset\{Date, DatePrefixFilter, DateTrimFilter};
use Common\Validator\DobValidator;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{Digits, NotEmpty, StringLength};
use Mezzio\Csrf\CsrfGuardInterface;

/**
 * Class RequestActivationKey
 * @package Actor\Form
 */
class RequestActivationKey extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'request_activation_key';

    /**
     * RequestActivationKey constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'opg_reference_number',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'first_names',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'last_name',
            'type' => 'Text',
        ]);

        $this->add(new Date('dob'));

        $this->add([
            'name' => 'postcode',
            'type' => 'Text',
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'opg_reference_number' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => StripSpacesAndHyphens::class]
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter the LPA reference number',
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 12,
                            'messages'  => [
                                StringLength::TOO_LONG => 'The LPA reference number you entered is too long',
                                StringLength::TOO_SHORT => 'The LPA reference number you entered is too short'
                            ],
                        ],
                    ],
                    [
                        'name'    => Digits::class,
                        'options' => [
                            'message' =>
                                'Enter the 12 numbers of the LPA reference number. ' .
                                'Do not include letters or other characters'
                        ],
                    ],
                ]
            ],
            'first_names' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your first names',
                        ],
                    ],
                ]
            ],
            'last_name' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your last name',
                        ],
                    ],
                ]
            ],
            'dob' => [
                'filters'  => [
                    ['name' => DateTrimFilter::class],
                    ['name' => DatePrefixFilter::class],
                ],
                'validators' => [
                    [
                        'name' => DobValidator::class,
                    ],
                ]
            ],
            'postcode' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => StringToUpper::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your postcode',
                        ],
                    ],
                ]
            ]
        ];
    }
}
