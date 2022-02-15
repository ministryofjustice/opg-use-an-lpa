<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Filter\ActorViewerCodeFilter;
use Common\Filter\StripSpacesAndHyphens;
use Common\Form\AbstractForm;
use Common\Form\Fieldset\Date;
use Common\Form\Fieldset\DatePrefixFilter;
use Common\Form\Fieldset\DateTrimFilter;
use Common\Validator\DobValidator;
use Laminas\Filter\StringToUpper;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

/**
 * Class LpaAdd
 *
 * @package Actor\Form
 */
class LpaAdd extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_add';

    /**
     * LpaAdd constructor.
     *
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'passcode',
                'type' => 'Text',

            ]
        );

        $this->add(
            [
                'name' => 'reference_number',
                'type' => 'Text',
            ]
        );

        $this->add(new Date('dob'));
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'passcode'         => [
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => ActorViewerCodeFilter::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter your activation key',
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => "/^(?![Cc][[:alnum:]]{12,}).*$/",
                            'message' => 'The activation key you entered is too long. '
                                . 'Check that you only entered the 12 letters and numbers that follow the C-',
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
                                StringLength::TOO_LONG  => 'The activation key you entered is too long',
                                StringLength::TOO_SHORT => 'The activation key you entered is too short',
                            ],
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => "/^[[:alnum:]]{12}$/",
                            'message' => 'Enter an activation key in the correct format',
                        ],
                    ],
                ],
            ],
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
                        'name'    => StringLength::class,
                        'options' => [
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
                        'name'    => Regex::class,
                        'options' => [
                            'pattern' => '/^\d{12}$/',
                            'message' => 'Enter the LPA reference number in the correct format',
                        ],
                    ],
                ],
            ],
            'dob'              => [
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
