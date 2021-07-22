<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class ActorRole extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'actor_role';

    /**
     * AddLpaTriage constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name'       => 'actor_role_radio',
                'type'       => 'Radio',
                'options'    => [
                    'value_options' => [
                        'Donor'     => 'Donor',
                        'Attorney'  => 'Attorney'
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
            'actor_role_radio' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Select whether you are the donor or an attorney on the LPA',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
