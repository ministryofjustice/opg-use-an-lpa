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

class DateOfBirth extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'date_of_birth';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(new Date('dob'));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'dob' => [
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
                                DobValidator::DATE_EMPTY => 'Enter the date of birth.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
