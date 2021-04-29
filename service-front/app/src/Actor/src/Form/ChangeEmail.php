<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Email;
use Common\Validator\EmailAddressValidator;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class ChangeEmail extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = "change-email";

    public const NEW_EMAIL_NOT_DIFFERENT = 'NewEmailNotDifferent';
    public const INVALID_PASSWORD = 'invalidPassword';

    /**
     * Error messages
     * @var array
     */
    protected array $messageTemplates = [
        self::INVALID_PASSWORD => 'The password you entered is incorrect',
        self::NEW_EMAIL_NOT_DIFFERENT => 'The new email address you entered is the same as your current email address. They must be different.',
    ];

    /**
     * ChangeEmail constructor.
     * @param CsrfGuardInterface $guard
     */
    public function __construct(CsrfGuardInterface $guard)
    {
        parent::__construct(self::FORM_NAME, $guard);

        $this->add(new Email('new_email_address'));

        $this->add([
            'name' => 'current_password',
            'type' => 'Password',
        ]);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification()
    {
        return [
            'new_email_address' => [
                'required'   => true,
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
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter your new email address',
                            ],
                        ],
                    ],
                    [
                        'name'                   => EmailAddressValidator::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                EmailAddressValidator::INVALID => 'Enter an email address in the correct format, like name@example.com',
                            ],
                        ],
                    ]
                ],
            ],
            'current_password'  => [
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
