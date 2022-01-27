<?php

declare(strict_types=1);

namespace Common\Form\Element;

use Common\Validator\CsrfGuardValidator as CsrfValidator;
use Laminas\Form\Element\Csrf as LaminasCsrf;
use Laminas\Validator\Csrf as LaminasCsrfValidator;

/**
 * Class Csrf
 * @package Common\Form\Element
 */
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
            $this->setCsrfValidator(new CsrfValidator($csrfOptions));
        }
        return $this->csrfValidator;
    }
}
