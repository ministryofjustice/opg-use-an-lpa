<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Common\Form\Fieldset\{Date, DatePrefixFilter, DateTrimFilter};
use Common\Validator\DobValidator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class RequestDateOfBirth extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'request_activation_key_dob';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(new Date('dob'));
    }

    /**
     * @return array
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
