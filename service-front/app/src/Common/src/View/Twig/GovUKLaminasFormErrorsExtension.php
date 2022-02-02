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
            )
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
     */
    public function errorMessage(Environment $twigEnv, ElementInterface $element): string
    {
        $template = $twigEnv->load(self::THEME_FILE);

        return $template->renderBlock('error_message', [
            'element'     => $element,
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
     */
    public function errorSummary(Environment $twigEnv, FormInterface $form): string
    {
        $template = $twigEnv->load(self::THEME_FILE);

        // if the form has no overall errors it'll be an empty array
        $errors = $form->getMessages();
        $invalidInput = $form->getInputFilter()->getInvalidInput();

        //  Flatten each set of messages for each input
        foreach ($invalidInput as $name => $input) {
            $errors[$name] = $this->flattenMessages($input->getMessages());
        }

        return $template->renderBlock('error_summary', [
            'errors' => $errors,
        ]);
    }

    /**
     * @param array $messages
     * @return array
     */
    private function flattenMessages(array $messages): array
    {
        $messagesToPrint = [];

        array_walk_recursive($messages, function ($item) use (&$messagesToPrint) {
            $messagesToPrint[] = $item;
        });

        return $messagesToPrint;
    }
}
