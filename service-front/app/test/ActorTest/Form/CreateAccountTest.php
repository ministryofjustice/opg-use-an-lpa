<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\CreateAccount;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\{Checkbox, Hidden, Password};
use Common\Form\Element\Email;

class CreateAccountTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

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
            '__csrf'                => Csrf::class,
            'email'                 => Email::class,
            'show_hide_password'    => Password::class,
            'terms'                 => Checkbox::class,
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CreateAccount($guardProphecy->reveal());
    }
}
