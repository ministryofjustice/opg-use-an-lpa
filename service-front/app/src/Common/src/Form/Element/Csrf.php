<?php

declare(strict_types=1);

namespace Common\Form\Element;

use Common\Validator\CsrfGuardValidator as CsrfValidator;
use Zend\Form\Element\Csrf as ZendCsrf;

/**
 * Class Csrf
 * @package Common\Form\Element
 */
class Csrf extends ZendCsrf
{
    /**
     * Overridden function required to implement our custom CsrfValidator
     *
     * @return \Zend\Validator\Csrf
     */
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
