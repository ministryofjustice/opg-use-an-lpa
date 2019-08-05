<?php

namespace Common\Form\Fieldset;

use Zend\Filter\StringTrim;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Regex;

/**
 * Class Date
 *
 * This fieldset is only suitable for catching AD dates
 *
 * @package Common\Form\Fieldset
 */
class Date extends Fieldset implements InputFilterProviderInterface
{
    /**
     * Date constructor.
     * @param null $name
     * @param array $options
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->add([
            'name' => 'day',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'month',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'year',
            'type' => 'Text',
        ]);
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification() : array
    {
        return [
            'day' => [
                'allow_empty'       => true, // Use these 2 flags so the default NotEmpty validator is not injected
                'continue_if_empty' => true,
                'filters'  => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    new Regex([
                        'pattern' => '/\b(0?[1-9]|[12][0-9]|3[01])\b/',
                        'message' => 'Enter a valid day.'
                    ])
                ]
            ],
            'month' => [
                'allow_empty'       => true, // Use these 2 flags so the default NotEmpty validator is not injected
                'continue_if_empty' => true,
                'filters'  => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    new Regex([
                        'pattern' => '/\b(0?[1-9]|1[0-2])\b/',
                        'message' => 'Enter a valid month.'
                    ])
                ]
            ],
            'year' => [
                'allow_empty'       => true, // Use these 2 flags so the default NotEmpty validator is not injected
                'continue_if_empty' => true,
                'filters'  => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [
                    new Regex([
                        'pattern' => '/\b([0-9]?[0-9]?[0-9]?[0-9])\b/',
                        'message' => 'Enter a valid year.'
                    ])
                ]
            ],
        ];
    }
}
