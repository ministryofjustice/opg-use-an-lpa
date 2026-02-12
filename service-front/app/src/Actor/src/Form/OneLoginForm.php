<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

class OneLoginForm extends AbstractForm
{
    public const string FORM_NAME = 'one_login';

    public const string ACCESS_DENIED_ERROR           = 'access_denied';
    public const string SESSION_MISSING_ERROR         = 'session_missing';
    public const string TEMPORARILY_UNAVAILABLE_ERROR = 'temporarily_unavailable';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
