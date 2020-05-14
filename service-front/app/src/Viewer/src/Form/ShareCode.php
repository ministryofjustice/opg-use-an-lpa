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
    public const VIEW_TIMEOUT_MESSAGE = "Do you want to continue?" .
    " You have not used this service for 30 minutes." .
    " Click continue to use any details you entered";

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard, self::VIEW_TIMEOUT_MESSAGE);

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
                        'pattern' => "/(^[vV]?|^)(?'dash' |-|){0,6}[[:alnum:]]{4}(\g'dash'){0,6}[[:alnum:]]{4}(\g'dash'){0,6}[[:alnum:]]{4}/",
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
