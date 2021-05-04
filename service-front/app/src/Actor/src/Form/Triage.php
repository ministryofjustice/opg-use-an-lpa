<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;

class Triage extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'triage';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name'       => 'triageEntry',
            'type'       => 'Radio',
            'options'    => [
                'value_options' => [
                    'yes' => 'yes',
                    'no' => 'no',
                ]
            ]
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'triageEntry' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Select yes if you have a Use a lasting power of attorney account',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
