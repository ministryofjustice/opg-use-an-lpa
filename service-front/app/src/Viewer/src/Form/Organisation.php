<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class Organisation extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'organisation_name';

    protected array $messageTemplates = [
        self::NOT_SAME => "Do you want to continue?" .
            " You have not used this service for 30 minutes." .
            " Click continue to use any details you entered"
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'organisation',
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
            'organisation' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message'  => 'Enter your organisation name',
                        ],
                    ]
                ]
            ],
        ];
    }
}
