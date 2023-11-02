<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class PasswordReset extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'password-reset';

    public function __construct(CsrfGuardInterface $guard)
    {
        parent::__construct(self::FORM_NAME, $guard);

        $this->add(
            [
            'name' => 'password',
            'type' => 'Password',
            ]
        );
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
        ];
    }
}
