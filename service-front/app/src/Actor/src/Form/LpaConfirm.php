<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

class LpaConfirm extends AbstractForm
{
    const FORM_NAME = 'lpa_add';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
