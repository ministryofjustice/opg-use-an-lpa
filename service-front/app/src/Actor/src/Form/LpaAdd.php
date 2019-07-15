<?php

declare(strict_types=1);

namespace Actor\Form;

use Actor\Form\Fieldset\Date;
use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Class LpaAdd
 * @package Actor\Form
 */
class LpaAdd extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'lpa_add';

    /**
     * LpaAdd constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'passcode',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'reference_number',
            'type' => 'Text',
        ]);

        $this->add(new Date('dob'));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification() : array
    {
        return [
            //TODO
        ];
    }
}
