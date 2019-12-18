<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordReset;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Password;

class PasswordResetTest extends TestCase implements TestsZendForm
{
    use ZendFormTests;

    /** @var PasswordReset */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'password-reset';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'password'         => Password::class,
            'password_confirm' => Password::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new PasswordReset($guardProphecy->reveal());
    }
}
