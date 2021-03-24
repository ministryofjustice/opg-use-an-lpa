<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordChange;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\Password;

class PasswordChangeTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var PasswordChange */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'password-change';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class,
            'current_password' => Password::class,
            'new_password' => Password::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new PasswordChange($guardProphecy->reveal());
    }
}
