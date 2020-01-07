<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Common\Form\AbstractForm;
use Zend\InputFilter\InputFilter;

trait ZendFormTests
{
    /** @test */
    public function it_is_a_form()
    {
        $this->assertInstanceOf(AbstractForm::class, $this->getForm());

        $this->assertEquals($this->getFormName(), $this->getForm()->getName());
    }

    /** @test */
    public function it_declares_all_necessary_inputs()
    {
        $formElements = $this->getForm()->getElements();

        foreach ($formElements as $formElementName => $formElement) {
            if (!isset($this->getFormElements()[$formElementName])) {
                $this->fail(sprintf('No class type expectation found for element "%s"', $formElementName));
            }

            $expectedElementClass = $this->getFormElements()[$formElementName];
            $elementClass = get_class($formElement);

            if ($expectedElementClass != $elementClass) {
                $this->fail(sprintf('Class type expectation failure for "%s": Expecting %s but found %s', $formElementName, $expectedElementClass, $elementClass));
            }

            //  Put an assertion in here so that the test isn't flagged as risky
            $this->assertEquals($expectedElementClass, $elementClass);
        }
    }

    /** @test */
    public function it_declares_all_neccessary_input_filters()
    {
        $this->assertIsArray($this->getForm()->getInputFilterSpecification());
        $this->assertInstanceOf(InputFilter::class, $this->getForm()->getInputFilter());

        foreach ($this->getFormElements() as $elementName => $elementType) {
            if ($elementName === '__csrf') { continue; }

            $this->assertArrayHasKey($elementName, $this->getForm()->getInputFilterSpecification());
        }
    }
}