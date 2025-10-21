<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Common\Validator\NotEmptyConditional;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class VerificationCodeReceiver extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'verification_code';

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
                'name' => 'attorney_name',
                'type' => 'Text',
            ]
        );

        $this->add(
            [
                 'name'    => 'verification_code_receiver',
                 'type'    => 'Radio',
                 'options' => [
                     'value_options' => [
                         'Attorney' => 'Attorney',
                         'Donor'    => 'Donor',
                     ],
                 ],
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'verification_code_receiver' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => [
                                NotEmpty::IS_EMPTY => 'Select who the paper verification code was sent to',
                            ],
                        ],
                    ],
                ],
            ],
            'attorney_name'              => [
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmptyConditional::class,
                        'options' => [
                            'message'         => 'Enter attorney name',
                            'dependant'       => 'verification_code_receiver',
                            'dependant_value' => 'Donor',
                        ],
                    ],
                ],
            ],
        ];
    }
}
