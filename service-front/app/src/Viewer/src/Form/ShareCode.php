<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;

class ShareCode extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'share_code';


    protected array $messageTemplates = [
        self::NOT_SAME => "Do you want to continue?" .
            " You have not used this service for 30 minutes." .
            " Click continue to use any details you entered"
    ];

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

    public function getInputFilterSpecification(): array
    {
        return [
            'lpa_code' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter the LPA access code',
                        ],
                    ],
                    new Regex([
                        'pattern' => '/^[[:alnum:]]{4,4}(?\'dash\' |-)?[[:alnum:]]{4,4}(\g{dash})?[[:alnum:]]{4,4}$/',
                        'message' => [
                            Regex::NOT_MATCH => 'LPA access codes are 13 numbers and letters long and start with a V'
                        ]
                    ])
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
