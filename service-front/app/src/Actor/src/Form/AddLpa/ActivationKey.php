<?php

declare(strict_types=1);

namespace Actor\Form\AddLpa;

use Common\Filter\ActorViewerCodeFilter;
use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class ActivationKey extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_add_activation_key';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'activation_key',
                'type' => 'Text',
            ]
        );
    }

    /**
     * @return             array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'activation_key' => [
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
                            'pattern' => '/^(?![Cc][[:alnum:]]{12,}).*$/',
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
                            'pattern' => '/^[[:alnum:]]{12}$/',
                            'message' => 'Enter an activation key in the correct format',
                        ],
                    ],
                ],
            ],
        ];
    }
}
