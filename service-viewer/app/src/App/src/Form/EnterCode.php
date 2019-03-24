<?php
declare(strict_types=1);
namespace App\Form;

use Zend\Form\Form as ZendForm;

class EnterCode extends ZendForm
{

    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);
    }

    public function init()
    {
        $this->add(

        );
    }

}
