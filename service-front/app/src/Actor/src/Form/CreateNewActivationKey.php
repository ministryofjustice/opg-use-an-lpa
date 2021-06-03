<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Class CreateNewActivationKey
 * @package Actor\Form
 */
class CreateNewActivationKey extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'create_new_activation_key';

    /**
     * CreateNewActivationKey constructor.
     * @param CsrfGuardInterface $csrfGuard
     */
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'reference_number',
            'type' => 'Hidden',
        ]);
        $this->add([
            'name' => 'first_names',
            'type' => 'Hidden',
        ]);
        $this->add([
             'name' => 'last_name',
             'type' => 'Hidden',
        ]);
        $this->add([
             'name' => 'dob',
             'type' => 'Hidden',
        ]);
        $this->add([
             'name' => 'postcode',
             'type' => 'Hidden',
        ]);
        $this->add([
             'name' => 'force_activation_key',
             'type' => 'Hidden',
        ]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification() : array
    {
        return [
            'reference_number' => [
                'required'   => true,
            ],
            'first_names' => [
                'required'   => true,
            ],
            'last_name' => [
                'required'   => true,
            ],
            'dob' => [
                'required'   => true,
            ],
            'postcode' => [
                'required'   => true,
            ],
            'force_activation_key' => [
                'required'   => true,
            ],

        ];
    }
}
