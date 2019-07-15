<?php

namespace Actor\Form\Fieldset;

use Zend\Form\Fieldset;

/**
 * Class Date
 * @package Actor\Form\Fieldset
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
            'name' => $this->getName() . '-day',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => $this->getName() . '-month',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => $this->getName() . '-year',
            'type' => 'Text',
        ]);
    }
}
