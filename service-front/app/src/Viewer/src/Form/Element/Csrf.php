<?php

declare(strict_types=1);

namespace Viewer\Form\Element;

use Zend\Form\Element\Csrf as ZendCsrf;
use Viewer\Validator\CsrfGuardValidator as CsrfValidator;

class Csrf extends ZendCsrf
{
    public function getCsrfValidator()
    {
        if (null === $this->csrfValidator) {
            $csrfOptions = $this->getCsrfValidatorOptions();
            $csrfOptions = array_merge($csrfOptions, ['name' => $this->getName()]);
            $this->setCsrfValidator(new CsrfValidator($csrfOptions));
        }
        return $this->csrfValidator;
    }
}