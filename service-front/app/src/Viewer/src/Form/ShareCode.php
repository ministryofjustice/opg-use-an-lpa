<?php

declare(strict_types=1);

namespace Viewer\Form;

use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Filter\StringTrim;
use Zend\Form\Form;
use Viewer\Form\Element\Csrf;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Regex;

class ShareCode extends Form implements InputFilterProviderInterface
{
    public function __construct(CsrfGuardInterface $csrfGuard)
    {
        parent::__construct();

        $this->add([
            'name' => 'lpa_code',
            'type'  => 'Text',
        ]);
        $this->add(
            new Csrf(
                '__csrf',
                [
                    'csrf_options' => [
                        'guard' => $csrfGuard
                    ]
                ]
            )
        );
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
                        'pattern' => '/[\w\d]{4,4}-[\w\d]{4,4}-[\w\d]{4,4}/',
                        'message' => [
                            Regex::NOT_MATCH => 'Enter an LPA share code in the correct format.'
                        ]
                    ])
                ]
            ]
        ];
    }
}