<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;


class RequestContactDetails extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'contact-details';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'telephone',
                'type' => 'Text',
            ]
        );

        $this->add(
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
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'telephone' => [
                'required' => false,
            ],
            'no_phone' => [
                'required' => false
            ]
        ];
    }
}
