<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\Fieldset\Date;
use Actor\Form\LpaAdd;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Text;
use Zend\InputFilter\InputFilter;

class LpaAddTest extends TestCase
{
    /**
     * @var LpaAdd
     */
    private $form;

    /**
     * @var array
     */
    private $elements = [
        '__csrf'           => Csrf::class,
        'passcode'         => Text::class,
        'reference_number' => Text::class,
        'dob'              => Date::class,
    ];

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);

        $this->form = new LpaAdd($guardProphecy->reveal());
    }


    public function testIsAForm()
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertInstanceOf(LpaAdd::class, $this->form);

        $this->assertEquals('lpa_add', $this->form->getName());
    }

    public function testInputs()
    {
        $formElements = $this->form->getElements();

        foreach ($formElements as $formElementName => $formElement) {
            if (!isset($this->elements[$formElementName])) {
                $this->fail(sprintf('No class type expectation found for element "%s"', $formElementName));
            }

            $expectedElementClass = $this->elements[$formElementName];
            $elementClass = get_class($formElement);

            if ($expectedElementClass != $elementClass) {
                $this->fail(sprintf('Class type expectation failure for "%s": Expecting %s but found %s', $formElementName, $expectedElementClass, $elementClass));
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
