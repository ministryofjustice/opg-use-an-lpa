<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Common\Validator\NotEmptyConditional;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class RequestPostcode extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'request_activation_key_postcode';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
               'name' => 'postcode',
               'type' => 'Text',
            ]
        );

        $this->add(
            [
               'name'    => 'live_in_uk',
               'type'    => 'Radio',
               'options' => [
                   'value_options' => [
                       'Yes' => 'Yes',
                       'No'  => 'No',
                   ],
               ],
            ]
        );
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        $emptyValidator = [
            'name'    => NotEmpty::class,
            'options' => [
                'message' => 'Enter your postcode',
            ],
        ];
        return [
            'live_in_uk' => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'Select if you live in the UK or do not live in the UK',
                            ],
                        ],
                    ],
                ],
            ],
            'postcode'   => [
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => StringToUpper::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmptyConditional::class,
                        'options' => [
                            'message'         => 'Enter your postcode',
                            'dependant'       => 'live_in_uk',
                            'dependant_value' => 'No',
                        ],
                    ],
                ],
            ],
        ];
    }
}
