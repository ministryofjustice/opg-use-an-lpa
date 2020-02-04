<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\NotEmpty;
use Zend\Validator\Regex;

class ShareCode extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'share_code';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'lpa_code',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'donor_surname',
            'type' => 'Text',
        ]);
    }

    public function getInputFilterSpecification() : array
    {
        return [
            'lpa_code' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    new Regex([
                        'pattern' => '/^[[:alnum:]]{4,4}(?\'dash\' |-)?[[:alnum:]]{4,4}(\g{dash})?[[:alnum:]]{4,4}$/',
                        'message' => [
                            Regex::NOT_MATCH => 'LPA access codes are 13 numbers and letters long and start with a V'
                        ]
                    ]),
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter the LPA access code',
                        ],
                    ]
                ]
            ],
            'donor_surname' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter the donor\'s surname',
                        ],
                    ]
                ]
            ]
        ];
    }
}