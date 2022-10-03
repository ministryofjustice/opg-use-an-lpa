<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

class CheckDetailsAndConsent extends AbstractForm
{
    public const FORM_NAME = 'check_details_and_consent';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
