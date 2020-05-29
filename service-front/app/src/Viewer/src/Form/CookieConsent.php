<?php

declare(strict_types=1);

namespace Viewer\Form;

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
            'options'    => [
                'value_options' => [
                    'no' => 'no',
                    'yes' => 'yes',
                ]
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
