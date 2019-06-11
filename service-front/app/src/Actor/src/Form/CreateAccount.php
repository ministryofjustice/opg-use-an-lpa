<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractCsrfForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;

class CreateAccount extends AbstractCsrfForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'create_account';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'email',
            'type'  => 'Text',
        ]);

        $this->add([
            'name' => 'email_confirm',
            'type'  => 'Text',
        ]);

        $this->add([
            'name' => 'password',
            'type'  => 'Password',
        ]);

        $this->add([
            'name' => 'password_confirm',
            'type'  => 'Password',
        ]);

        $this->add([
            'name' => 'terms',
            'type'  => 'Checkbox',
        ]);
    }

    public function getInputFilterSpecification() : array
    {
        $emailFieldsConfig = [
            'required' => true,
            'filters'  => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
                //  TODO
            ]
        ];

        $passwordFieldsConfig = [
            'required' => true,
            'filters'  => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
                //  TODO
            ]
        ];

        return [
            'email'            => $emailFieldsConfig,
            'email_confirm'    => $emailFieldsConfig,
            'password'         => $passwordFieldsConfig,
            'password_confirm' => $passwordFieldsConfig,
        ];
    }
}
