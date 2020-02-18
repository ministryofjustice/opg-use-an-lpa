<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\Login;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsZendForm, ZendFormTests};
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\{Password, Text};

class LoginTest extends TestCase implements TestsZendForm
{
    use ZendFormTests;

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
            'email'            => Text::class,
            'password'         => Password::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new Login($guardProphecy->reveal());
    }
}
