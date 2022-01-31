<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestDateOfBirth;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class RequestDateOfBirthTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestDateOfBirth $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key_dob';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class,
            'dob'    => Date::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestDateOfBirth($guardProphecy->reveal());
    }
}
