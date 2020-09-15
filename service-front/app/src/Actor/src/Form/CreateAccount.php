<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Email;
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
    protected array $messageTemplates = [
        self::NEW_EMAIL_CONFLICT => 'Sorry, there was a problem with that email address. Please try a different one'
    ];

    /**
     * CreateAccount constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(new Email('email'));

        $this->add([
            'name' => 'show_hide_password',
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
                                NotEmpty::IS_EMPTY => 'Enter an email address in the correct format, like name@example.com',
                            ],
                        ],
                    ],
                    [
                        'name'                   => EmailAddressValidator::class,
                        'break_chain_on_failure' => true,
                    ]
                ],
            ],
            'show_hide_password'    => [
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
                                StringLength::TOO_SHORT => 'Password must be 8 characters or more',
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
                                Identical::NOT_SAME => 'Passwords do not match',
                            ],
                        ],
                    ],
                ],
            ],
            'terms'            => [
                'required'      => true,
                'error_message' => 'You must accept the terms of use to create an account',
                'validators'    => [
                    [
                        'name'    => Identical::class,
                        'break_chain_on_failure' => true,
                        'options' => [
                            'token' => '1',
                            'literal' => true,
                            'messages' => [
                                Identical::NOT_SAME => 'You must accept the terms of use to create an account',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
