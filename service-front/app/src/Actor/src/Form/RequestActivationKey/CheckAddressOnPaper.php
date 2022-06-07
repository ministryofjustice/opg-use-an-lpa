<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class CheckAddressOnPaper extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'actor_address-check';

    /**
     * AddLpaTriage constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name'       => 'actor_address_check_radio',
                'type'       => 'Radio',
                'options'    => [
                    'value_options' => [
                        'Yes' => 'Yes',
                        'No'  => 'No',
                        'Not sure'  => 'Not sure'
                    ]
                ]
            ]
        );
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'actor_address_check_radio' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Select whether this is the same address as your address on the paper LPA',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
