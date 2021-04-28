<?php

namespace ActorTest\Form;

use Actor\Form\RequestActivationKey;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use Laminas\Form\Element\Text;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class RequestActivationKeyTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestActivationKey $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'                => Csrf::class,
            'opg_reference_number'  => Text::class,
            'first_names'           => Text::class,
            'last_name'             => Text::class,
            'dob'                   => Date::class,
            'postcode'              => Text::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestActivationKey($guardProphecy->reveal());
    }
}
