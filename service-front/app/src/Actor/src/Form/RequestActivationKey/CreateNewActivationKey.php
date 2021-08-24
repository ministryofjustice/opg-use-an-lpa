<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;

/**
 * Class CreateNewActivationKey
 * @package Actor\Form
 */
class CreateNewActivationKey extends AbstractForm
{
    public const FORM_NAME = 'create_new_activation_key';

    /**
     * CreateNewActivationKey constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);
    }
}
