<?php

declare(strict_types=1);

namespace Viewer\View\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zend\Form\ElementInterface;
use Zend\Form\FormInterface;

class GovUKZendFormErrorsExtension extends AbstractExtension
{
    const THEME_FILE='@partials/govuk_error.html.twig';

    public function getFunctions() : array
    {
        return [
            new TwigFunction('govuk_error_class', [$this, 'errorClass']),
            new TwigFunction('govuk_error', [$this, 'errorMessage'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('govuk_error_summary', [$this, 'errorSummary'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function errorClass(ElementInterface $element) : string
    {
        if (count($element->getMessages()) > 0) {
            return "govuk-input--error";
        }

        return "";
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
    public function errorMessage(Environment $twigEnv, ElementInterface $element) : string
    {
        $template = $twigEnv->load(self::THEME_FILE);

        return $template->renderBlock('error_message', [
            'id'     => $element->getName(),
            'errors' => $this->flattenMessages($element->getMessages()),
        ]);
    }

    public function errorSummary(Environment $twigEnv, FormInterface $form) : string {
        $template = $twigEnv->load(self::THEME_FILE);

        $errors = [];
        $invalidInput = $form->getInputFilter()->getInvalidInput();

        foreach ($invalidInput as $name => $input) {
            $errors[$name] = $this->flattenMessages($input->getMessages());
        }

        return $template->renderBlock('error_summary', [
            'form_name' => $form->getName(),
            'errors' => $errors,
        ]);
    }

    private function flattenMessages(array $messages) : array {
        $messagesToPrint = [];
        array_walk_recursive($messages, function ($item) use (&$messagesToPrint) {
            $messagesToPrint[] = $item;
        });
        return $messagesToPrint;
    }

}