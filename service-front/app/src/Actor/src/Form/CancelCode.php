<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class CancelCode extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'cancel_code';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
            'name' => 'viewer_code',
            'type' => 'Hidden',
            ]
        );
        $this->add(
            [
            'name' => 'organisation',
            'type' => 'Hidden',
            ]
        );
        $this->add(
            [
            'name' => 'lpa_token',
            'type' => 'Hidden',
            ]
        );
    }

    /**
     * @return             array
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'viewer_code'  => [
                'required' => true,
            ],
            'organisation' => [
                'required' => true,
            ],
            'lpa_token'    => [
                'required' => true,
            ],
        ];
    }
}
