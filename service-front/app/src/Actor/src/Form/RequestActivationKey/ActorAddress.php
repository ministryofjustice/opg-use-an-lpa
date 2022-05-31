<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class ActorAddress extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'actor_address';

    /**
     * DonorDetails constructor
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'actor_address_1',
                'type' => 'Text',
            ]
        );

        $this->add(
            [
                'name' => 'actor_address_2',
                'type' => 'Text',
            ]
        );

        $this->add(
            [
                'name' => 'actor_address_town',
                'type' => 'Text',
            ]
        );
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'actor_address_1'    => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmpty::class,
                        'options' => [
                            'message'  => 'Enter your building and street',
                        ],
                    ],
                ]
            ],
            'actor_address_2'    => [
                'required'   => false,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
            ],
            'actor_address_town' => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmpty::class,
                        'options' => [
                            'message'  => 'Enter your town or city',
                        ],
                    ],
                ]
            ],
        ];
    }
}
