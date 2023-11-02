<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Email;
use Common\Validator\EmailAddressValidator;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Hidden;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class PasswordResetRequest extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'password-reset-request';

    public function __construct(CsrfGuardInterface $guard)
    {
        parent::__construct(self::FORM_NAME, $guard);

        $this->add(new Email('email'));
        $this->add(new Email('email_confirm'));
        $this->add(new Hidden('forced'));
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
     *
     * @return             array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'email'         => [
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
                                NotEmpty::IS_EMPTY
                                    => 'Enter an email address in the correct format, like name@example.com',
                            ],
                        ],
                    ],
                    [
                        'name'                   => EmailAddressValidator::class,
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
            'email_confirm' => [
                'required'   => true,
                'filters'    => [
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
            'forced'        => [
                'required' => false,
            ],
        ];
    }
}
