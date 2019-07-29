<?php

declare(strict_types=1);

namespace Common\Form;

use Common\Form\Element\Csrf;
use Common\Validator\CsrfGuardValidator;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Exception\InvalidArgumentException;
use Zend\Form\Form;

abstract class AbstractForm extends Form
{
    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [];

    /**
     * @var array This, and its associated functions below allow form level error messages not attached to
     *            any individual form elements. Something that Zend form does not provide OOTB.
     */
    protected $errorMessages = [];

    public function __construct(string $formName, CsrfGuardInterface $csrfGuard)
    {
        parent::__construct($formName);

        $this->add(
            new Csrf(
                '__csrf',
                [
                    'csrf_options' => [
                        'guard' => $csrfGuard,
                        'messageTemplates' => [
                            CsrfGuardValidator::NOT_SAME => "As you have not used this service for over 60 minutes, the page has timed out. We've now refreshed the page - please try to sign in again."
                        ],
                    ],
                ]
            )
        );
    }

    /**
     * Allows the addition of form level error messages to a form. These will typically be displayed at the
     * summary level on the page.
     *
     * @param string $messageKey The key to a message stored in the messageTemplates array of the form
     * @param string $elementName An optional field name to link the error message to
     */
    public function addErrorMessage(string $messageKey, string $elementName = "") : void
    {
        if (! isset($this->messageTemplates[$messageKey])) {
            throw new InvalidArgumentException("No message template exists for key '$messageKey'");
        }

        if ($elementName !== "" && ! $this->has($elementName)) {
            throw new InvalidArgumentException("No form element named '$elementName' found");
        }

        $this->errorMessages[$elementName] = [$this->messageTemplates[$messageKey]];
    }

    /**
     * An array containing error messages attached to the form rather than its child elements.
     *
     * @return array
     */
    public function getErrorMessages() : array
    {
        return $this->errorMessages ;
    }

    /**
     * @inheritDoc
     */
    public function getMessages($elementName = null) : array
    {
        if ($elementName) {
            $messages = parent::getMessages($elementName);
        } else {
            $messages = array_merge($this->getErrorMessages(), parent::getMessages($elementName));
        }
        return $messages;
    }

}
