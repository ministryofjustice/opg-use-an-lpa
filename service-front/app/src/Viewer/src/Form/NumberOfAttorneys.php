<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Laminas\Filter\Digits;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Number;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class NumberOfAttorneys extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'pv_number_of_attorneys';

    /** @var array<array-key, mixed> */
    protected array $messageTemplates = [
        self::NOT_SAME => 'Do you want to continue?' .
            ' You have not used this service for 30 minutes.' .
            ' Click continue to use any details you entered',
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'no_of_attorneys',
                'type' => 'number',
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'no_of_attorneys' => [
                'required'  => false,
                'filters'   => [
                    ['name' => StringTrim::class],
                    ['name' => Digits::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter number of attorney',
                        ],
                    ],
                ],
            ],
        ];
    }
}
