<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class AddressOnPaper extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'address_on_paper';

    /**
     * AddLpaTriage constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name'       => 'address_on_paper_area',
                'label'      => 'What is your address',
                'type'       => 'Textarea',
            ]
        );
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'address_on_paper_area' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'messages'           => [
                                NotEmpty::IS_EMPTY => 'Enter your address on the paper LPA',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
