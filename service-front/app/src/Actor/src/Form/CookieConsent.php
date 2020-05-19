<?php

declare(strict_types=1);

namespace Actor\Form;

use Application\Form\AbstractCsrfForm;
use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;

class CookieConsent extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'cookieConsent';

    /**
     * Error codes
     * @const string
     */
    const INVALID_LOGIN = 'invalidLogin';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name'       => 'usageCookies',
            'type'       => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options'    => [
                'value_options' => [
                    'yes' => [
                        'label' => 'Use cookies that measure my website use',
                        'value' => 'yes',
                    ],
                    'no' => [
                        'label' => 'Do not use cookies that measure my website use',
                        'value' => 'no',
                    ],
                ],
            ]
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'usageCookies' => [
                'required'   => true,
            ]
        ];
    }
}
