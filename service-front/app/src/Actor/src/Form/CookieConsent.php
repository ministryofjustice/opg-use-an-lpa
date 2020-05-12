<?php

declare(strict_types=1);

namespace Actor\Form;

use Application\Form\AbstractCsrfForm;

class CookieConsent extends AbstractCsrfForm
{
    public function init()
    {
        $this->setName('cookieConsent');

        $this->add([
            'name'       => 'usageCookies',
            'type'       => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required'   => true,
            'options'    => [
                'value_options' => [
                    'yes' => [
                        'label' => 'Yes, allow usage cookies',
                        'value' => 'yes',
                    ],
                    'no' => [
                        'label' => 'No, do not allow usage cookies ',
                        'value' => 'no',
                    ],
                ],
            ]
        ]);

        parent::init();
    }
}
