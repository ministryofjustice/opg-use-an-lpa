<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Laminas\Filter\Callback;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;

class LpaCheck extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'lpa_check';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
                'name' => 'lpa_reference',
                'type' => 'Text',
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lpa_reference' => [
                'required'   => true,
                'filters'    => [
                    ['name' => StringTrim::class],
                    [
                        // If user enters below, automatically correct to M-7890-0400-4000
                        // m789004004000
                        //m 7890 0400 4000
                        //M-789004004000
                        'name'    => Callback::class,
                        'options' => [
                            'callback' => static function (?string $value): ?string {
                                if ($value === null) {
                                    return null;
                                }

                                // Remove all spaces and non-alphanumeric characters
                                $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));

                                // Must start with M and have 12 digits
                                if (preg_match('/^M\d{12}$/', $clean)) {
                                    // Insert dashes: M-####-####-####
                                    return sprintf(
                                        'M-%s-%s-%s',
                                        substr($clean, 1, 4),
                                        substr($clean, 5, 4),
                                        substr($clean, 9, 4)
                                    );
                                }

                                // If it doesn’t match expected raw pattern, return as-is
                                return $value;
                            },
                        ],
                    ],
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'message' => 'Enter your LPA reference number',
                        ],
                    ],
                    [
                        'name'                   => StringLength::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'encoding' => 'UTF-8',
                            'min'      => 13,
                            'max'      => 16,
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
                            'pattern' => '/^M-\d{4}-\d{4}-\d{4}$/',
                            'message' => 'Enter LPA reference number in the correct format',
                        ],
                    ],
                ],
            ],
        ];
    }
}
