<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Email;
use Common\Validator\EmailAddressValidator;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class CreateAccount extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'create_account';

    public const NEW_EMAIL_CONFLICT = 'NewEmailConflict';

    /**
     * Error messages
     *
     * @var string[]
     */
    protected array $messageTemplates = [
        self::NEW_EMAIL_CONFLICT => 'Sorry, there was a problem with that email address. Please try a different one',
    ];

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

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'email'              => [
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
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Enter an email address in the correct format, ' .
                                    'like name@example.com',
                            ],
                        ],
                    ],
                    [
                        'name'                   => EmailAddressValidator::class,
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
            'show_hide_password' => [
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
                            'min'      => 12,
                            'messages' => [
                                StringLength::TOO_SHORT => 'Password must be 12 characters or more',
                            ],
                        ],
                    ],
                ],
            ],
            'terms'              => [
                'required'      => true,
                'error_message' => 'You must accept the terms of use to create an account',
                'validators'    => [
                    [
                        'name'                   => Identical::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'token'    => '1',
                            'literal'  => true,
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
