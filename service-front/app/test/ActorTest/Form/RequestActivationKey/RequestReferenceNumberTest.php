<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Laminas\Form\Element\Text;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Common\Service\Features\FeatureEnabled;

class RequestReferenceNumberTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestReferenceNumber $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key_reference_number';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'                => Csrf::class,
            'opg_reference_number'  => Text::class,
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);

        $this->form = new RequestReferenceNumber($guardProphecy->reveal(), $featureEnabledProphecy->reveal());
    }
}
