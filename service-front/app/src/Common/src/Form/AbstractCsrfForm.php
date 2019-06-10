<?php

declare(strict_types=1);

namespace Common\Form;

use Common\Form\Element\Csrf;
use Common\Validator\CsrfGuardValidator;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Form;

class AbstractCsrfForm extends Form
{
    public function __construct(string $formName, CsrfGuardInterface $csrfGuard)
    {
        parent::__construct($formName);

        $this->add(
            new Csrf(
                '__csrf',
                [
                    'csrf_options' => [
                        'guard' => $csrfGuard,
                        'messageTemplates' => [
                            CsrfGuardValidator::NOT_SAME => "As you have not used this service for over 60 minutes, the page has timed out. We've now refreshed the page - please try to sign in again."
                        ],
                    ],
                ]
            )
        );
    }
}