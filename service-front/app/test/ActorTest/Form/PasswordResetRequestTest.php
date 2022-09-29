<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordResetRequest;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Element\Email;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Hidden;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PasswordResetRequestTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected PasswordResetRequest $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'password-reset-request';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'        => Csrf::class,
            'email'         => Email::class,
            'email_confirm' => Email::class,
            'forced'        => Hidden::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new PasswordResetRequest($guardProphecy->reveal());
    }
}
