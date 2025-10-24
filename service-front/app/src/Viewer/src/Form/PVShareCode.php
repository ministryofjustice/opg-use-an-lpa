<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Filter\ConvertQuotesToApostrophe;
use Common\Filter\ShareCodeFilter;
use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class PVShareCode extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'share_code';

    /** @var array<array-key, mixed> */
    protected array $messageTemplates = [
        self::NOT_SAME => 'Do you want to continue?' .
            ' You have not used this service for 30 minutes.' .
            ' Click continue to use any details you entered',
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'lpa_code',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'donor_surname',
            'type' => 'Text',
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'lpa_code'      => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => ShareCodeFilter::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter an access or paper verification code in the correct format',
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => '/^([A-Z0-9]{12}|P(-[A-Z0-9]{4}){3}-[A-Z0-9]{2})$/',
                            'message' => 'Enter an access or paper verification code in the correct format',
                        ],
                    ],
                ],
            ],
            'donor_surname' => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => ConvertQuotesToApostrophe::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter the donor’s last name',
                        ],
                    ],
                ],
            ],
        ];
    }
}
