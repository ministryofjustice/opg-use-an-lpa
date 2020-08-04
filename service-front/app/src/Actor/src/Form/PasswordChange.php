<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Validator\PasswordValidator;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * Class PasswordChange
 * @package Actor\Form\
 */
class PasswordChange extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = "password-change";

    /**
     * Error codes
     * @const string
     */
    public const INVALID_PASSWORD = 'invalidPassword';

    /**
     * Error messages
     * @var array
     */
    protected array $messageTemplates = [
        self::INVALID_PASSWORD => 'Current password is incorrect',
    ];

    /**
     * PasswordChange constructor.
     * @param CsrfGuardInterface $guard
     */
    public function __construct(CsrfGuardInterface $guard)
    {
        parent::__construct(self::FORM_NAME, $guard);

        $this->add([
            'name' => 'current_password',
            'type' => 'Password',
        ]);

        $this->add([
            'name' => 'new_password',
            'type' => 'Password',
        ]);

        $this->add([
            'name' => 'new_password_confirm',
            'type' => 'Password',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getInputFilterSpecification()
    {
        return [
            'current_password' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter your current password',
                            ],
                        ],
                    ],
                ],
            ],
            'new_password'  => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter your new password',
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
                        'name'    => Identical::class,
                        'options' => [
                            'token'    => 'new_password_confirm',
                            'messages' => [
                                Identical::NOT_SAME => 'Passwords do not match',
                            ],
                        ],
                    ],
                    [
                        'name' => PasswordValidator::class,
                    ],
                ],
            ],
            'new_password_confirm' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter your password again to confirm it',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
