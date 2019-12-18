<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\PasswordReset;
use Actor\Form\PasswordResetRequest;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Text;

class PasswordResetRequestTest extends TestCase implements TestsZendForm
{
    use ZendFormTests;

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
            'email'            => Text::class,
            'email_confirm'    => Text::class,
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new PasswordResetRequest($guardProphecy->reveal());
    }
}
