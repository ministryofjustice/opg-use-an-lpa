<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\CreateAccount;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsZendForm, ZendFormTests};
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\{Checkbox, Password, Text};

class CreateAccountTest extends TestCase implements TestsZendForm
{
    use ZendFormTests;

    /** @var CreateAccount */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'create_account';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'email'            => Text::class,
            'password'         => Password::class,
            'password_confirm' => Password::class,
            'terms'            => Checkbox::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CreateAccount($guardProphecy->reveal());
    }
}
