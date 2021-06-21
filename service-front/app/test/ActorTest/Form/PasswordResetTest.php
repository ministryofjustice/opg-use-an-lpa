<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordReset;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\Password;

class PasswordResetTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

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
            'password'         => Password::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new PasswordReset($guardProphecy->reveal());
    }
}
