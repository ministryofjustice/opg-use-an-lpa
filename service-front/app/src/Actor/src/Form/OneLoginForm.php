<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

class OneLoginForm extends AbstractForm
{
    public const string FORM_NAME = 'one_login';

    public const ACCESS_DENIED_ERROR           = 'access_denied';
    public const SESSION_MISSING_ERROR         = 'session_missing';
    public const TEMPORARILY_UNAVAILABLE_ERROR = 'temporarily_unavailable';

    /**
     * Error messages
     *
     * @var string[]
     */
    protected array $messageTemplates = [
        self::ACCESS_DENIED_ERROR           => 'Tried to login however access is denied.',
        self::SESSION_MISSING_ERROR         => 'We are aware of an issue with people accessing the service using older
            versions of iOS (the operating system for iPhones and iPads). We are working to resolve this as soon as
            possible. In the meantime, you may wish to try using a different device.',
        self::TEMPORARILY_UNAVAILABLE_ERROR => 'One Login is temporarily unavailable.',
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
