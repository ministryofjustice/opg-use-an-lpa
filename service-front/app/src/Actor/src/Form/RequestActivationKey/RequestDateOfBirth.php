<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Filter\StripSpacesAndHyphens;
use Common\Form\AbstractForm;
use Common\Form\Fieldset\{Date, DatePrefixFilter, DateTrimFilter};
use Common\Validator\DobValidator;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{Digits, NotEmpty, StringLength};
use Mezzio\Csrf\CsrfGuardInterface;

/**
 * Class RequestActivationKey
 * @package Actor\Form\RequestActivationKey
 */
class RequestDateOfBirth extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'request_activation_key';

    /**
     * RequestActivationKey constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
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
                'filters'  => [
                    ['name' => DateTrimFilter::class],
                    ['name' => DatePrefixFilter::class],
                ],
                'validators' => [
                    [
                        'name' => DobValidator::class,
                    ],
                ]
            ]
        ];
    }
}
