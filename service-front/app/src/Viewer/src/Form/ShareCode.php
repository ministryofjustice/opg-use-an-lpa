<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Filter\ActorViewerCodeFilter;
use Common\Form\AbstractForm;
use Laminas\Filter\StringToUpper;
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
                    ['name' => ActorViewerCodeFilter::class]
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your LPA access code',
                        ],
                    ],
                    [
                        'name'    => Regex::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'pattern' => "/^[[:alnum:]]{12}$/",
                            'message' => 'Enter LPA access code in the correct format',
                        ]
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
                            'message'  => 'Enter the donor\'s last name',
                        ],
                    ]
                ]
            ]
        ];
    }
}
