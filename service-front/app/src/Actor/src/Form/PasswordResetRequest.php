<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Validator\EmailAddressValidator;
use Common\Validator\IdenticalEmailCheck;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringToLower;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Identical;
use Zend\Validator\NotEmpty;

class PasswordResetRequest extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = "password-reset-request";

    /**
     * PasswordReset constructor.
     * @param CsrfGuardInterface $guard
     */
    public function __construct(CsrfGuardInterface $guard)
    {
        parent::__construct(self::FORM_NAME, $guard);

        $this->add([
            'name' => 'email',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'email_confirm',
            'type' => 'Text',
        ]);
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'email'            => [
                'required' => true,
//                'filters'  => [
//                    [
//                        'name' => StringToLower::class,
//                    ],
//                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Enter your email address',
                            ],
                        ],
                    ],
                    [
                        'name'                   => EmailAddressValidator::class,
                        'break_chain_on_failure' => true,
                    ]
                ],
            ],
            'email_confirm'    => [
                'required' => true,
//                'filters'  => [
//                    [
//                        'name' => StringToLower::class,
//                    ],
//                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Confirm your email address',
                            ],
                        ],
                    ],
                    [
                        'name'                   => Identical::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'token'    => 'email',
                            'messages' => [
                                Identical::NOT_SAME => 'The emails did not match',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
