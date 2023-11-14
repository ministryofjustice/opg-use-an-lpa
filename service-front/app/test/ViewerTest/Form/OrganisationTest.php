<?php

declare(strict_types=1);

namespace ViewerTest\Form;

use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilter;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Viewer\Form\Organisation;

class OrganisationTest extends TestCase
{
    use ProphecyTrait;

    private Organisation $form;

    /**
     * @var string[]
     */
    private array $elements = [
        '__csrf'       => Csrf::class,
        'organisation' => Text::class,
    ];

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);

        $this->form = new Organisation($guardProphecy->reveal());
    }

    public function testIsAForm()
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertInstanceOf(Organisation::class, $this->form);

        $this->assertEquals('organisation_name', $this->form->getName());
    }

    public function testInputs()
    {
        $formElements = $this->form->getElements();

        foreach ($formElements as $formElementName => $formElement) {
            if (!isset($this->elements[$formElementName])) {
                $this->fail('No class type expectation found for ' . $formElementName);
            }

            $expectedElementClass = $this->elements[$formElementName];
            $elementClass         = $formElement::class;

            if ($expectedElementClass !== $elementClass) {
                $this->fail(
                    sprintf(
                        'Class type expectation failure for "%s": Expecting %s but found %s',
                        $formElementName,
                        $expectedElementClass,
                        $elementClass
                    )
                );
            }

            //  Put an assertion in here so that the test isn't flagged as risky
            $this->assertEquals($expectedElementClass, $elementClass);
        }
    }

    public function testInputFilter()
    {
        $this->assertIsArray($this->form->getInputFilterSpecification());
        $this->assertInstanceOf(InputFilter::class, $this->form->getInputFilter());
    }
}
