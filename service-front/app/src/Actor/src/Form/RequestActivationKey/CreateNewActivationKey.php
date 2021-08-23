<?php

declare(strict_types=1);

namespace Actor\Form\RequestActivationKey;

use Common\Filter\ToDateTime;
use Common\Form\AbstractForm;
use Laminas\Filter\Boolean;
use Laminas\Filter\ToInt;
use Laminas\InputFilter\InputFilterProviderInterface;
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
