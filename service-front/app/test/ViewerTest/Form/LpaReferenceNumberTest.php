<?php

declare(strict_types=1);

namespace ViewerTest\Form;

use Laminas\InputFilter\InputFilter;
use Viewer\Form\LpaReferenceNumber;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Viewer\Form\PVShareCode;

class LpaReferenceNumberTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected LpaReferenceNumber $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'lpa_reference_number';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'        => Csrf::class,
            'lpa_reference' => Text::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new LpaReferenceNumber($guardProphecy->reveal());
    }

    public function testIsAForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertInstanceOf(LpaReferenceNumber::class, $this->form);

        $this->assertEquals('lpa_reference_number', $this->form->getName());
    }

    public function testInputFilter(): void
    {
        $this->assertIsArray($this->form->getInputFilterSpecification());
        $this->assertInstanceOf(InputFilter::class, $this->form->getInputFilter());
    }
}
