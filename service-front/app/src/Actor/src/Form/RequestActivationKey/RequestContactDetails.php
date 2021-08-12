<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Actor\Validator\OptionSelectedValidator;
use Common\Form\AbstractForm;
use Laminas\Filter\Digits;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class RequestContactDetails extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'contact-details';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $fieldset = new Fieldset('telephone_option');
        $fieldset->add(
            [
                'name' => 'telephone',
                'type' => 'Text',
            ]
        );

        $fieldset->add(
            [
                'name' => 'no_phone',
                'type' => 'Checkbox',
                'options' => [
                    'label' => 'A checkbox',
                    'checked_value' => 'yes',
                    'unchecked_value' => 'no',
                ],
                'attributes' => [
                    'value' => 'no',
                ],
            ]
        );

        $this->add($fieldset);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'telephone_option' => [
                'validators' => [
                    [
                        'name' => OptionSelectedValidator::class
                    ]
                ]
            ],
            'telephone' => [
                'required' => false,
                'filters'  => [
                    ['name' => Digits::class],
                ],
            ],
            'no_phone' => [
                'required' => false
            ]
        ];
    }
}
