<?php

declare(strict_types=1);

namespace Actor\Form;

use Actor\Form\Fieldset\Date;
use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;
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
                    new StringLength([
                        'min' => 12,
                        'max' => 12,
                        'message' => 'Enter one-time passcode in the correct format.',
                    ])
                ]
            ],
            'reference_number' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    new Regex([
                        'pattern' => '/7\d{11}/',
                        'message' => [
                            Regex::NOT_MATCH => 'Enter an LPA reference number in the correct format.'
                        ]
                    ])
                ]
            ],
        ];
    }
}
