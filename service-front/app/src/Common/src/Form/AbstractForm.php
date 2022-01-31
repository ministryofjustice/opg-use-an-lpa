<?php

declare(strict_types=1);

namespace Common\Form;

use Common\Form\Element\Csrf;
use Laminas\Form\Exception\InvalidArgumentException;
use Laminas\Form\Form;
use Mezzio\Csrf\CsrfGuardInterface;

abstract class AbstractForm extends Form
{
    public const NOT_SAME = 'notSame';

    /**
     * Error messages templates
     */
    protected array $messageTemplates = [];

    /**
     * @var array This, and its associated functions below allow form level error messages not attached to
     *            any individual form elements. Something that Zend form does not provide OOTB.
     */
    protected array $errorMessages = [];

    public function __construct(string $formName, CsrfGuardInterface $csrfGuard)
    {
        parent::__construct($formName);

        $this->add(
            new Csrf(
                '__csrf',
                [
                    'csrf_options' => [
                        'guard' => $csrfGuard,
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
    public function addErrorMessage(string $messageKey, string $elementName = ''): void
    {
        if (! isset($this->messageTemplates[$messageKey])) {
            throw new InvalidArgumentException("No message template exists for key '$messageKey'");
        }

        if ($elementName !== '' && ! $this->has($elementName)) {
            throw new InvalidArgumentException("No form element named '$elementName' found");
        }

        if ($elementName === '') {
            //add error to just the error summary section
            $this->errorMessages[''] = [$this->messageTemplates[$messageKey]];
        } else {
            //add error to error messages for the element
            $formElement = $this->getElements()[$elementName];

            $messages = $formElement->getMessages();
            array_push($messages, $this->messageTemplates[$messageKey]);

            $formElement->setMessages($messages);
        }
    }

    /**
     * An array containing error messages attached to the form rather than its child elements.
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * @inheritDoc
     */
    public function getMessages($elementName = null): array
    {
        if ($elementName) {
            $messages = parent::getMessages($elementName);
        } else {
            $messages = array_merge($this->getErrorMessages(), parent::getMessages($elementName));
        }

        return $messages;
    }
}
