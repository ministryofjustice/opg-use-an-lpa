<?php

namespace Common\Form\Fieldset;

use Laminas\Form\Fieldset;

/**
 * Class Date
 *
 * This fieldset is only suitable for catching AD dates
 * To validate this fieldset use the Common\Validator\DateValidator or any of it's descendant validators
 *
 * @package Common\Form\Fieldset
 */
class Date extends Fieldset
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
}
