<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Class ConfirmDeleteAccountHandler
 * @package Actor\Form
 */
class ConfirmDeleteAccount extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'confirm_delete_account';

    /**
     * ConfirmDeleteAccountHandler constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'account_id',
            'type' => 'Hidden',
        ]);

        $this->add([
            'name' => 'user_email',
            'type' => 'Hidden',
        ]);
    }

    public function getInputFilterSpecification() : array
    {
        return [
            'account_id' => [
                'required' => true,
            ],
            'user_email' => [
                'required' => true,
            ]
        ];
    }
}
