<?php

declare(strict_types=1);

namespace Actor\Form;

use Laminas\Form\Element;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Class CookieConsent
 * @package Actor\Form
 */
class CookieConsent extends AbstractForm
{
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //------------------------

        $field = new Element\Radio('usage-cookies');
        $input = new Input($field->getName());

        $input->getValidatorChain()->attach();

        $field->setValueOptions([
            'no' => 'no',
            'yes' => 'yes',
        ]);

        $this->add($field);
        $inputFilter->add($input);
    }
}
