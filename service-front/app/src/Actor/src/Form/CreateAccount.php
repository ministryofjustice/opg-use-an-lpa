<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Validator\EmailAddressValidator;
use Common\Validator\PasswordValidator;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringToLower;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Laminas\Filter\StringTrim;

/**
 * Class CreateAccount
 * @package Actor\Form
 */
class CreateAccount extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'create_account';

    public const NEW_EMAIL_CONFLICT = 'NewEmailConflict';

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [
        self::NEW_EMAIL_CONFLICT => 'Sorry, there was a problem with that email address. Please try a different one'
    ];

    /**
     * CreateAccount constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'email',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'Password',
        ]);

        $this->add([
            'name' => 'password_confirm',
            'type' => 'Password',
        ]);

        $this->add([
            'name'  => 'terms',
            'type'  => 'Checkbox',
            'value' => 1,
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'email'            => [
                'required' => true,
                'filters'  => [
                    [
                        'name' => StringToLower::class,
                    ],
                    [
                        'name' => StringTrim::class,

                    ],
                ],
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
            'password'         => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter your password',
                            ],
                        ],
                    ],
                    [
                        'name'    => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min'      => 8,
                            'messages' => [
                                StringLength::TOO_SHORT => 'Your password must be at least eight characters long',
                            ],
                        ],
                    ],
                    [
                        'name' => PasswordValidator::class,
                    ],
                    [
                        'name'    => Identical::class,
                        'options' => [
                            'token'    => 'password_confirm',
                            'messages' => [
                                Identical::NOT_SAME => 'The passwords did not match',
                            ],
                        ],
                    ],
                ],
            ],
            'password_confirm' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Confirm your password',
                            ],
                        ],
                    ],
                ],
            ],
            'terms'            => [
                'required'      => true,
                'error_message' => 'You must accept the terms of use',
                'validators'    => [
                    [
                        'name'    => Identical::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'token' => '1',
                            'literal' => true,
                            'messages' => [
                                Identical::NOT_SAME => 'You must accept the terms of use',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
