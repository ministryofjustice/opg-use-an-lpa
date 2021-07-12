<?php

namespace ActorTest\Form;

use Actor\Form\RequestActivationKey\RequestPostcode;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use Laminas\Form\Element\Text;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class RequestPostcodeTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestPostcode $form;

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
            'postcode'              => Text::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestPostcode($guardProphecy->reveal());
    }
}
