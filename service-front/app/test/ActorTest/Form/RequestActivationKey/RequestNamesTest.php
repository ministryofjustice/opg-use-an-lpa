<?php

namespace ActorTest\Form;

use Actor\Form\RequestActivationKey\RequestNames;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use Laminas\Form\Element\Text;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class RequestNamesTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestNames $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key_names';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'                => Csrf::class,
            'first_names'           => Text::class,
            'last_name'             => Text::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestNames($guardProphecy->reveal());
    }
}
