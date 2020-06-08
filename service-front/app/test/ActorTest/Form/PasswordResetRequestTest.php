<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordResetRequest;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\Text;

class PasswordResetRequestTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var PasswordResetRequest */
    protected $form;

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
            '__csrf'           => Csrf::class,
            'email'            => Email::class,
            'email_confirm'    => Email::class,
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new PasswordResetRequest($guardProphecy->reveal());
    }
}
