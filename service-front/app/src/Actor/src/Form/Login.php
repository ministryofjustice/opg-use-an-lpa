<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Validator\EmailAddressValidator;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringToLower;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Filter\StringTrim;

class Login extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'login';

    /**
     * Error codes
     * @const string
     */
    const INVALID_LOGIN = 'invalidLogin';

    /**
     * Error codes
     * @const string
     */
    const EMAIL_CHANGE = 'emailChangeRequested';

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID_LOGIN => 'Email and password combination not recognised. Please try signing in again below or create an account',
        self::EMAIL_CHANGE => 'We have sent an email to the new email you requested. Please follow the instructions to verify and complete the change'
    ];

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
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'email' => [
                'required'   => true,
                'filters'    => [
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
            'password' => [
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
                ],
            ],
        ];
    }
}
