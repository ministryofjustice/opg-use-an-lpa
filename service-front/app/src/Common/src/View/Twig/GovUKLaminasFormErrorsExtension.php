<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Laminas\Form\ElementInterface;
use Laminas\Form\FormInterface;

class GovUKLaminasFormErrorsExtension extends AbstractExtension
{
    public const THEME_FILE = '@partials/govuk_error.html.twig';

    /**
     * @return array
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'govuk_error',
                [$this, 'errorMessage'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'govuk_error_summary',
                [$this, 'errorSummary'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                "translate_validation_messages",
                [$this,'applyTranslatedMessages']
            ),
        ];
    }

    /**
     * @param Environment $twigEnv
     * @param ElementInterface $element
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * Generate an error message
     */
    public function errorMessage(Environment $twigEnv, ElementInterface $element): string
    {
        $template = $twigEnv->load(self::THEME_FILE);

        return $template->renderBlock('error_message', [
            'id'     => $element->getName(),
            'errors' => $this->flattenMessages($element->getMessages()),
        ]);
    }

    /**
     * @param Environment $twigEnv
     * @param FormInterface $form
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * Generate an error summary
     */
    public function errorSummary(Environment $twigEnv, FormInterface $form): string
    {
        $template = $twigEnv->load(self::THEME_FILE);

        // if the form has no overall errors it'll be an empty array

        $returnedErrors = $form->getMessages();
        $errors = $form->getMessages();
        $invalidInput = $form->getInputFilter()->getInvalidInput();

        //  Flatten each set of messages for each input
        foreach ($invalidInput as $name => $input) {
            $inputMessages = $input->getMessages();

            // check and replace where you can the error message.
            foreach ($inputMessages as $key => $value) {
                if (key_exists($name, $returnedErrors) && key_exists($key, $returnedErrors[$name])) {
                    $inputMessages[$key] = $returnedErrors[$name][$key];
                }
            }

            // this sets the error array format needed for the sumary render.
            $errors[$name] = $this ->flattenMessages($inputMessages);
        }

        return $template->renderBlock('error_summary', [
            'errors' => $errors,
        ]);
    }

    /**
     * @param array $messages
     * @return array
     * Flattens the input validation messages into a form acceptable to the error summary.
     */
    private function flattenMessages(array $messages): array
    {
        $messagesToPrint = [];

        array_walk_recursive($messages, function ($item) use (&$messagesToPrint) {
            $messagesToPrint[] = $item;
        });

        return $messagesToPrint;
    }

    /**
     * @param FormInterface $form
     * @param $messages
     * Apply  any translated messages, if found in the twig template.
     */
    public function applyTranslatedMessages(FormInterface $form, $messages)
    {
        //1. retrieve messages that are already set.
        $formMessages = $form->getMessages();

        //2. replace messages.
        $replacedErrors = $this->replaceMessages($messages, $formMessages);

        //3. reapply to the form.
        $form->setMessages($replacedErrors);
    }


    /**
     * @param $translatedMessages
     * @param $formMessages
     * @return array
     * replaces messages with the translated messages.
     * this is assumed to be both in the same structure all the way through.
     */
    public function replaceMessages($translatedMessages, $formMessages)
    {
        $replacedArray = [];
        foreach ($formMessages as $elementName => $messages) {
            if (key_exists($elementName, $translatedMessages)) {
                $result = $translatedMessages[$elementName];
                $replacedArray[$elementName] = $messages;

                foreach ($replacedArray[$elementName] as $message => $value) {
                    if (key_exists($message, $result)) {
                        $replacedArray[$elementName][$message] = $result[$message];
                    }
                }
            }
        }
        return $replacedArray;
    }
}
