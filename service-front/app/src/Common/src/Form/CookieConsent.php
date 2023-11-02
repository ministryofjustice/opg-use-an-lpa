<?php

declare(strict_types=1);

namespace Common\Form;

use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class CookieConsent extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'cookieConsent';

    /**
     * Error codes
     *
     * @const string
     */
    public const INVALID_LOGIN = 'invalidLogin';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
            'name'    => 'usageCookies',
            'type'    => 'Radio',
            'options' => [
                'value_options' => [
                    'no'  => 'no',
                    'yes' => 'yes',
                ],
            ],
            ]
        );
        $this->add(
            [
            'name' => 'referer',
            'type' => 'Hidden',
            ]
        );
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'usageCookies' => [
                'required' => true,
            ],
            'referer'      => [
                'required' => true,
            ],
        ];
    }
}
