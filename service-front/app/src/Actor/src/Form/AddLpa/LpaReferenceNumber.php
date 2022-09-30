<?php

declare(strict_types=1);

namespace Actor\Form\AddLpa;

use Common\Filter\StripSpacesAndHyphens;
use Common\Form\AbstractForm;
use Common\Validator\LuhnCheck;
use Common\Validator\SiriusReferenceStartsWithCheck;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class LpaReferenceNumber extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_add_reference_number';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'reference_number',
                'type' => 'Text',
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
            'reference_number' => [
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => StripSpacesAndHyphens::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter the LPA reference number',
                        ],
                    ],
                    [
                        'name'                   => StringLength::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'encoding' => 'UTF-8',
                            'min'      => 12,
                            'max'      => 12,
                            'messages' => [
                                StringLength::TOO_LONG  => 'The LPA reference number you entered is too long',
                                StringLength::TOO_SHORT => 'The LPA reference number you entered is too short',
                            ],
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => '/^\d{12}$/',
                            'message' => 'Enter the LPA reference number in the correct format',
                        ],
                    ],
                    [
                        'name'                   => SiriusReferenceStartsWithCheck::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => LuhnCheck::class,
                    ],
                ],
            ],
        ];
    }
}
