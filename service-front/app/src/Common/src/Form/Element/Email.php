<?php

declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Email as LaminasEmail;

/**
 * Class Email
 * @package Common\Form\Element
 */
class Email extends LaminasEmail
{
    public function __construct($name = null, $options = [])
    {
        if (null !== $name) {
            $this->setName($name);
        }

        if (! empty($options)) {
            $this->setOptions($options);
        }
    }

    public function getInputSpecification(): array
    {
        return [
            'name' => $this->getName(),
            'required' => true,
            'filters' => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
            ],
        ];
    }
}
