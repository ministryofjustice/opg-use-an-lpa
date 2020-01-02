<?php

declare(strict_types=1);

namespace Viewer\Form;

use Common\Form\AbstractForm;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Regex;

class ShareCode extends AbstractForm implements InputFilterProviderInterface
{
    const FORM_NAME = 'share_code';

    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct(self::FORM_NAME, $csrfGuard);

        $this->add([
            'name' => 'lpa_code',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'donor_surname',
            'type' => 'Text',
        ]);
    }

    public function getInputFilterSpecification() : array
    {
        return [
            'lpa_code' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ],
            ],
            'donor_surname' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                ]
            ]
        ];
    }
}