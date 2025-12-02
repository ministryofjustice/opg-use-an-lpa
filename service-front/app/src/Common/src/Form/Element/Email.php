<?php

declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Email as LaminasEmail;

/**
 * @psalm-suppress InvalidExtendClass
 */
class Email extends LaminasEmail
{
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getInputSpecification(): array
    {
        return [
            'name'       => $this->getName(),
            'required'   => true,
            'filters'    => [
                ['name' => StringTrim::class],
            ],
            'validators' => [],
        ];
    }
}
