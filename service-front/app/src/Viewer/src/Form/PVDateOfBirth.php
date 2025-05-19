<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Form\Fieldset\DateTrimFilter;
use Common\Validator\DobValidator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class PVDateOfBirth extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'pv_date_of_birth';

    /** @var array<array-key, mixed> */
    protected array $messageTemplates = [
        self::NOT_SAME => 'Do you want to continue?' .
            ' You have not used this service for 30 minutes.' .
            ' Click continue to use any details you entered',
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(new Date('attorney_dob'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
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