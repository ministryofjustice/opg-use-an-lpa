<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\NotEmpty;

class CreateShareCode extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'lpa_sharecode_create';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'org_name',
            'type' => 'Text',
        ]);
        $this->add([
            'name' => 'lpa_token',
            'type' => 'Hidden',
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'org_name' => [
                'required'   => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Enter an organisation name',
                            ],
                        ],
                    ],
                ],
            ],
            'lpa_token' => [
                'required'   => true,
            ],
        ];
    }
}
