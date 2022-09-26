<?php

declare(strict_types=1);

namespace Common\Form\Element;

use Common\Validator\CsrfGuardValidator;
use Laminas\Form\Element\Csrf as LaminasCsrf;
use Laminas\Validator\Csrf as LaminasCsrfValidator;

class Csrf extends LaminasCsrf
{
    /**
     * Overridden function required to implement our custom CsrfValidator
     *
     * @return LaminasCsrfValidator
     */
    public function getCsrfValidator(): LaminasCsrfValidator
    {
        if (null === $this->csrfValidator) {
            $csrfOptions = $this->getCsrfValidatorOptions();
            $csrfOptions = array_merge($csrfOptions, ['name' => $this->getName()]);
            $this->setCsrfValidator(new CsrfGuardValidator($csrfOptions));
        }
        return $this->csrfValidator;
    }
}
