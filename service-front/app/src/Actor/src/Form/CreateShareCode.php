<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;

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

    /**
     * @return array
     * @codeCoverageIgnore
     */
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
