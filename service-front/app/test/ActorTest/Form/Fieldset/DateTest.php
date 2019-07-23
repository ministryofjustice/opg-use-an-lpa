<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\Fieldset\Date;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Text;
use Zend\InputFilter\InputFilter;

class DateTest extends TestCase
{
    /**
     * @var Date
     */
    private $fieldset;

    public function setUp()
    {
        $this->fieldset = new Date('date-field');
    }

    public function testIsAForm()
    {
        $this->assertInstanceOf(Date::class, $this->fieldset);

        $this->assertEquals('date-field', $this->fieldset->getName());
    }

    public function testInputs()
    {
        $fieldsetElements = $this->fieldset->getElements();

        foreach ($fieldsetElements as $fieldsetElementName => $fieldsetElement) {
            $elements = [
                'date-field-day'   => Text::class,
                'date-field-month' => Text::class,
                'date-field-year'  => Text::class,
            ];

            if (!isset($elements[$fieldsetElementName])) {
                $this->fail(sprintf('No class type expectation found for element "%s"', $fieldsetElementName));
            }

            $expectedElementClass = $elements[$fieldsetElementName];
            $elementClass = get_class($fieldsetElement);

            if ($expectedElementClass != $elementClass) {
                $this->fail(sprintf('Class type expectation failure for "%s": Expecting %s but found %s', $fieldsetElementName, $expectedElementClass, $elementClass));
            }

            //  Put an assertion in here so that the test isn't flagged as risky
            $this->assertEquals($expectedElementClass, $elementClass);
        }
    }
}
