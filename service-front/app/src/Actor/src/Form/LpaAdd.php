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
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your one-time passcode',
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 12,
                            'message'  => 'Your passcode must be 12 characters long',
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/[a-zA-Z0-9]{12}/',
                            'message' => 'Your passcode must only include letters and numbers',
                        ],
                    ],
                ]
            ],
            'reference_number' => [
                'allow_empty'       => true, // Use these 2 flags so the default NotEmpty validator is not injected
                'continue_if_empty' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/7\d{11}/',
                            'message' => 'Enter an LPA reference number in the correct format',
                        ],
                    ],
                ]
            ],
        ];
    }
}
