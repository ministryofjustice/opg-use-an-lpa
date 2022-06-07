<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckAddressOnPaper;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Laminas\Form\Element\Radio;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class CheckAddressOnPaperTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected CheckAddressOnPaper $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'actor_address-check';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'actor_address_check_radio' => Radio::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CheckAddressOnPaper($guardProphecy->reveal());
    }
}
