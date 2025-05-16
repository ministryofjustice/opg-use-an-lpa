<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class LpaCheck extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_check';

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
                'name' => 'lpa_reference',
                'type' => 'Text',
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lpa_reference' => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter your LPA reference number',
                        ],
                    ],
                    [
                        'name'                   => StringLength::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 12,
                            'messages' => [
                                StringLength::TOO_LONG  => 'The LPA reference number you entered is too long',
                                StringLength::TOO_SHORT => 'The LPA reference number you entered is too short',
                            ],
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => '/^[[:alnum:]]{12}$/',
                            'message' => 'Enter LPA reference number in the correct format',
                        ],
                    ],
                ],
            ],
        ];
    }
}
