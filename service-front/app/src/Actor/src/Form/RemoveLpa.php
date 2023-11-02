<?php

declare(strict_types=1);

namespace Actor\Form;

use Common\Form\AbstractForm;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class RemoveLpa extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'remove_lpa';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add(
            [
            'name' => 'actor_lpa_token',
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
            'actor_lpa_token' => [
                'required' => true,
            ],
        ];
    }
}
