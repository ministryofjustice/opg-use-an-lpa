<?php

declare(strict_types=1);

namespace Actor\Form\AddLpa;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Form\Fieldset\DateTrimFilter;
use Common\Validator\DobValidator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class DateOfBirth extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_add_dob';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(new Date('dob'));
    }

    /**
     * @return             array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'dob' => [
                'filters'    => [
                    ['name' => DateTrimFilter::class],
                    ['name' => DatePrefixFilter::class],
                ],
                'validators' => [
                    [
                        'name' => DobValidator::class,
                    ],
                ],
            ],
        ];
    }
}
