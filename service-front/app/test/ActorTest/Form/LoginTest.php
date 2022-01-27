<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\Login;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Element\Email;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Password;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var Login */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'login';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'email'            => Email::class,
            'password'         => Password::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new Login($guardProphecy->reveal());
    }
}
