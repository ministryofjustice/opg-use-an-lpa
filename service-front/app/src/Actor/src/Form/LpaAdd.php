<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\NotEmpty;
use Zend\Validator\Regex;
use Zend\Validator\StringLength;

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
    public function getInputFilterSpecification() : array
    {
        return [
            'passcode' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter one-time passcode in the correct format.',
                            ],
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 12,
                            'messages' => [
                                StringLength::INVALID   => 'Enter one-time passcode in the correct format.',
                                StringLength::TOO_SHORT => 'Enter one-time passcode in the correct format.',
                                StringLength::TOO_LONG  => 'Enter one-time passcode in the correct format.',
                            ]
                        ],
                    ],
                ]
            ],
            'reference_number' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter an LPA reference number in the correct format.',
                            ],
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/7\d{11}/',
                            'message' => [
                                Regex::NOT_MATCH => 'Enter an LPA reference number in the correct format.',
                            ]
                        ],
                    ],
                ]
            ],
        ];
    }
}
