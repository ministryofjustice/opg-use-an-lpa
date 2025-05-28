<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilterProviderInterface;
use Mezzio\Csrf\CsrfGuardInterface;

class AttorneyDetailsForPV extends AbstractForm implements InputFilterProviderInterface
{
    public const FORM_NAME = 'attorney_details';

    /** @var array<array-key, mixed> */
    protected array $messageTemplates = [
        self::NOT_SAME => 'Do you want to continue?' .
            ' You have not used this service for 30 minutes.' .
            ' Click continue to use any details you entered',
    ];

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'no_of_attorneys',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'attorneys_name',
            'type' => 'Text',
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'no_of_attorneys' => [
                'required'   => false,
                'filters'    => [],
                'validators' => [],
            ],
            'attorneys_name'  => [
                'required'   => false,
                'filters'    => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [],
            ],
        ];
    }
}
