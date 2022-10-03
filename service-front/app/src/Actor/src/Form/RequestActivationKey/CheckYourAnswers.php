<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

class CheckYourAnswers extends AbstractForm
{
    public const FORM_NAME = 'check_answers';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
