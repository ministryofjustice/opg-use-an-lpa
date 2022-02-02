<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Laminas\Form\Element\Radio;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class ActorRoleTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected ActorRole $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'actor_role';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'actor_role_radio' => Radio::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new ActorRole($guardProphecy->reveal());
    }
}
