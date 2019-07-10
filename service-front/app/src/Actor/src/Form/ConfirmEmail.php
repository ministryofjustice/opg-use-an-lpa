<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Common\Validator\EmailAddressValidator;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringToLower;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Identical;
use Zend\Validator\NotEmpty;

/**
 * Class ConfirmEmail
 * @package Actor\Form
 */
class ConfirmEmail extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'confirm_email';

    /**
     * ConfirmEmail constructor.
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
            'name' => 'email_confirm',
            'type' => 'Text',
        ]);
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification() : array
    {
        return [
            'email' => [
                'required' => true,
                'filters'  => [
                    [
                        'name' => StringToLower::class,
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
            'email_confirm'    => [
                'required' => true,
                'filters'  => [
                    [
                        'name' => StringToLower::class,
                    ],
                ],
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
