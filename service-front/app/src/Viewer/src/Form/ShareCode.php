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
                'validators' => [
                    new Regex([
                        'pattern' => '/[\w\d\s\]{4,4}[\S\s?\-][\w\d\s]{4,4}[\S\s?\-][\w\d\s]{4,4}/',
                        'message' => [
                            Regex::NOT_MATCH => 'Enter an LPA share code in the correct format.'
                        ]
                    ])
                ]
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