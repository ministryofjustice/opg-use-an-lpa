<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Filter\AccessCodeFilter;
use Common\Filter\ConvertQuotesToApostrophe;
use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Mezzio\Csrf\CsrfGuardInterface;

final class AccessCode extends AbstractForm implements InputFilterProviderInterface
{
    public const string FORM_NAME = 'access_code';

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

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lpa_code'      => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                    ['name' => AccessCodeFilter::class],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter LPA access code in the correct format',
                        ],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern' => '/^[[:alnum:]]{12}$/',
                            'message' => 'Enter LPA access code in the correct format',
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
