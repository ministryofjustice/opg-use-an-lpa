<?php

namespace ActorTest\Form;

use Actor\Form\AddLpaTriage;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Radio;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class AddLpaTriageTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected AddLpaTriage $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'add_lpa_triage';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'                => Csrf::class,
            'activation_key_triage' => Radio::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new AddLpaTriage($guardProphecy->reveal());
    }
}
