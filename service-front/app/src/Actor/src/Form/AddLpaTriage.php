<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;

class AddLpaTriage extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'add_lpa_triage';

    /**
     * AddLpaTriage constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name'       => 'activation_key_triage',
            'type'       => 'Radio',
            'options'    => [
                'value_options' => [
                    'Yes' => 'Yes',
                    'No'  => 'No'
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
            'activation_key_triage' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Select if you have an activation key to add the LPA',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
