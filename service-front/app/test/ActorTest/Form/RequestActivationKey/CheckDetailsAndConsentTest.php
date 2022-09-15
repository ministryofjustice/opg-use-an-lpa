<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckDetailsAndConsent;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CheckDetailsAndConsentTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected CheckDetailsAndConsent $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'check_details_and_consent';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CheckDetailsAndConsent($guardProphecy->reveal());
    }
}
