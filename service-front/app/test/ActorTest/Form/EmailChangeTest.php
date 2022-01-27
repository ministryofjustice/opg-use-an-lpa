<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\ChangeEmail;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Element\Email;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Laminas\Form\Element\Password;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class EmailChangeTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var ChangeEmail */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'change-email';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class,
            'new_email_address' => Email::class,
            'current_password' => Password::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new ChangeEmail($guardProphecy->reveal());
    }
}
