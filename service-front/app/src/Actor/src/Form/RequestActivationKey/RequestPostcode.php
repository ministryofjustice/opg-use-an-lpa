<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
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

        $this->add([
            'name' => 'postcode',
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
            'postcode' => [
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => StringToUpper::class],
                ],
                'validators' => [
                    [
                        'name'    => NotEmpty::class,
                        'options' => [
                            'message' => 'Enter your postcode',
                        ],
                    ],
                ],
            ],
        ];
    }
}
