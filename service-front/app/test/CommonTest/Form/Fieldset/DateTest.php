<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\Fieldset\Date;
use PHPUnit\Framework\TestCase;
use Zend\Form\Element\Text;

class DateTest extends TestCase
{
    /**
     * @var Date
     */
    private $fieldset;

    public function setUp()
    {
        $this->fieldset = new Date('date');
    }

    public function testIsAForm()
    {
        $this->assertInstanceOf(Date::class, $this->fieldset);

        $this->assertEquals('date', $this->fieldset->getName());
    }

    public function testInputs()
    {
        $fieldsetElements = $this->fieldset->getElements();

        foreach ($fieldsetElements as $fieldsetElementName => $fieldsetElement) {
            $elements = [
                'day'   => Text::class,
                'month' => Text::class,
                'year'  => Text::class,
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
