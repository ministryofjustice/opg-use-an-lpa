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

/**
 * Class DonorDetails
 *
 * @package Actor\RequestActivationKey\Form
 */
class DonorDetails extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'donor_details';

    /**
     * DonorDetails constructor
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'first_names',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'last_name',
            'type' => 'Text',
        ]);

        $this->add(new Date('dob'));
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'first_names' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'message'  => 'Enter the donor\'s first names',
                        ],
                    ],
                ]
            ],
            'last_name' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'message'  => 'Enter the donor\'s last name',
                        ],
                    ],
                ]
            ],
            'dob' => [
                'filters'  => [
                    ['name' => DateTrimFilter::class],
                    ['name' => DatePrefixFilter::class],
                ],
                'validators' => [
                    [
                        'name' => DobValidator::class,
                        'options'                => [
                            'message'  => 'Enter the donor\'s dob',
                        ],
                    ],
                ]
            ]
        ];
    }
}
