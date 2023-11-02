<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Form\Fieldset\DateTrimFilter;
use Common\Validator\DobValidator;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class AttorneyDetails extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'attorney_details';

    /**
     * DonorDetails constructor
     *
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
            'name' => 'attorney_first_names',
            'type' => 'Text',
            ]
        );

        $this->add(
            [
            'name' => 'attorney_last_name',
            'type' => 'Text',
            ]
        );

        $this->add(new Date('attorney_dob'));
    }

    /**
     * @return             array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'attorney_first_names' => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmpty::class,
                        'options' => [
                            'message' => 'Enter the attorney\'s first names',
                        ],
                    ],
                ],
            ],
            'attorney_last_name'   => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmpty::class,
                        'options' => [
                            'message' => 'Enter the attorney\'s last name',
                        ],
                    ],
                ],
            ],
            'attorney_dob'         => [
                'required'   => true,
                'filters'    => [
                    ['name' => DateTrimFilter::class],
                    ['name' => DatePrefixFilter::class],
                ],
                'validators' => [
                    [
                        'name'    => DobValidator::class,
                        'options' => [
                            'messages' => [
                                DobValidator::DATE_EMPTY => 'Enter the attorney\'s date of birth',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
