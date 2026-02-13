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
     * @var string[] This, and its associated functions below allow form level error messages not attached to
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
