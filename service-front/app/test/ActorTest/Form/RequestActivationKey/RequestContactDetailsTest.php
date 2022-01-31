<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestContactDetails;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class RequestContactDetailsTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    protected RequestContactDetails $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'contact-details';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'    => Csrf::class,
            'telephone' => Text::class,
            'no_phone'  => Checkbox::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestContactDetails($guardProphecy->reveal());
    }
}
