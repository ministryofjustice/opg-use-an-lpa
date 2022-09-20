<?php

declare(strict_types=1);

namespace Common\Form\Fieldset;

use Laminas\Form\Fieldset;

/**
 * This fieldset is only suitable for catching AD dates
 * To validate this fieldset use the Common\Validator\DateValidator or any of it's descendant validators
 */
class Date extends Fieldset
{
    public function __construct(?string $name = null, array $options = [])
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
}
