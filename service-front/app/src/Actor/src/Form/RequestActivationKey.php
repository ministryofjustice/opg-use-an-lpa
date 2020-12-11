<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Form\Fieldset\DateTrimFilter;
use Common\Validator\DobValidator;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

/**
 * Class RequestActivationKey
 * @package Actor\Form
 */
class RequestActivationKey extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'request_activation_key';

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
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'opg_reference_number' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter the OPG reference number',
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 14,
                            'messages'  => [
                                StringLength::TOO_LONG => 'The OPG reference number you entered is too long',
                                StringLength::TOO_SHORT => 'The OPG reference number you entered is too short'
                            ],
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/^(\d{4}(?\'dash\' |-|)\d{4}(\g{dash})\d{4})$/',
                            'message' => 'Enter the 12 numbers of the OPG reference number. Do not include letters or other characters'
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
