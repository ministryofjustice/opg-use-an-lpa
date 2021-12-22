<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Filter\ConvertQuotesToApostrophe;
use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{NotEmpty};
use Mezzio\Csrf\CsrfGuardInterface;

/**
 * Class RequestActivationKey
 * @package Actor\Form
 */
class RequestNames extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'request_activation_key_names';

    /**
     * RequestActivationKey constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'first_names',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'last_name',
            'type' => 'Text',
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'first_names' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => ConvertQuotesToApostrophe::class]
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'message'  => 'Enter your first names',
                        ],
                    ],
                ]
            ],
            'last_name' => [
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'options'                => [
                            'message'  => 'Enter your last name',
                        ],
                    ],
                ]
            ]
        ];
    }
}
