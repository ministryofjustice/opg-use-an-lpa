<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Common\Validator\NotEmptyConditional;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Mezzio\Csrf\CsrfGuardInterface;

class CodeSentTo extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'code_sent_to';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'attorney_name',
                'type' => 'Text',
            ]
        );

        $this->add(
            [
                 'name'    => 'code_sent_to',
                 'type'    => 'Radio',
                 'options' => [
                     'value_options' => [
                         'Attorney' => 'Attorney',
                         'Donor'    => 'Donor',
                     ],
                 ],
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'code_sent_to'  => [
                'required'   => true,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => [
                                NotEmpty::IS_EMPTY => 'Select who the paper verification code was sent to',
                            ],
                        ],
                    ],
                ],
            ],
            'attorney_name' => [
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmptyConditional::class,
                        'options' => [
                            'message'         => 'Enter attorney name',
                            'dependant'       => 'code_sent_to',
                            'dependant_value' => 'Donor',
                        ],
                    ],
                ],
            ],
        ];
    }
}
