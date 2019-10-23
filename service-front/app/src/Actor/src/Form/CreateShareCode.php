<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;

class CreateShareCode extends AbstractForm
{
    const FORM_NAME = 'lpa_sharecode_create';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}