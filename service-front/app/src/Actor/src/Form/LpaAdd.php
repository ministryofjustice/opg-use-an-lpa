<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Validator\DobValidator;
use Laminas\Filter\StringToUpper;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

/**
 * Class LpaAdd
 * @package Actor\Form
 */
class LpaAdd extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'lpa_add';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'passcode',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'reference_number',
            'type' => 'Text',
        ]);

        $this->add(new Date('dob'));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'passcode' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => StringToUpper::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your activation key',
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 31,
                            'message'  => 'Your activation key must be 12 numbers and letters long',
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => "/(^[cC]?|^)(?'dash' |-|){0,6}[[:alnum:]]{4}(\g'dash'){0,6}[[:alnum:]]{4}(\g'dash'){0,6}[[:alnum:]]{4}$/",
                            'message' => 'Your activation key must only include letters, numbers and dashes',
                        ],
                    ],
                ]
            ],
            'reference_number' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter an LPA reference number',
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 14,
                            'message'  => 'The LPA reference number must be 12 numbers long',
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/^(\d{4}(?\'dash\' |-|)\d{4}(\g{dash})\d{4})$/',
                            'message' => 'The LPA reference number must only include numbers',
                        ],
                    ],
                ]
            ],
            'dob' => [
                'filters'  => [
                    [
                        'name' => DatePrefixFilter::class
                    ],
                ],
                'validators' => [
                    [
                        'name' => DobValidator::class,
                    ],
                ]
            ],
        ];
    }
}
