<?php

declare(strict_types=1);

namespace ViewerTest\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilter;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Viewer\Form\AttorneyDetailsForPV;

class AttorneyDetailsForPVTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected AttorneyDetailsForPV $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'attorney_details';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'              => Csrf::class,
            'number_of_attorneys' => Text::class,
            'attorney_name'       => Text::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new AttorneyDetailsForPV($guardProphecy->reveal());
    }

    public function testIsAForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertInstanceOf(AttorneyDetailsForPV::class, $this->form);

        $this->assertEquals('attorney_details', $this->form->getName());
    }

    public function testInputFilter(): void
    {
        $this->assertIsArray($this->form->getInputFilterSpecification());
        $this->assertInstanceOf(InputFilter::class, $this->form->getInputFilter());
    }
}
