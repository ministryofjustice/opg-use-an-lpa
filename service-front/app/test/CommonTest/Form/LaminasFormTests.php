<?php

declare(strict_types=1);

namespace CommonTest\Form;

use PHPUnit\Framework\Attributes\Test;
use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilter;

trait LaminasFormTests
{
    #[Test]
    public function it_is_a_form(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->getForm());

        $this->assertEquals($this->getFormName(), $this->getForm()->getName());
    }

    #[Test]
    public function it_declares_all_necessary_inputs(): void
    {
        $formElements = $this->getForm()->getElements();

        foreach ($formElements as $formElementName => $formElement) {
            if (!isset($this->getFormElements()[$formElementName])) {
                $this->fail(sprintf('No class type expectation found for element "%s"', $formElementName));
            }

            $expectedElementClass = $this->getFormElements()[$formElementName];
            $elementClass         = $formElement::class;

            if ($expectedElementClass === $elementClass) {
            } else {
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

    #[Test]
    public function it_declares_all_neccessary_input_filters(): void
    {
        if (method_exists($this->getForm(), 'getInputFilterSpecification')) {
            $this->assertIsArray($this->getForm()->getInputFilterSpecification());
            $this->assertInstanceOf(InputFilter::class, $this->getForm()->getInputFilter());

            foreach ($this->getFormElements() as $elementName => $elementType) {
                if ($elementName === '__csrf') {
                    continue;
                }
                $this->assertArrayHasKey($elementName, $this->getForm()->getInputFilterSpecification());
            }
        } else {
            $this->markTestSkipped('Form doesnt have an input filter. Test skipped');
        }
    }
}
