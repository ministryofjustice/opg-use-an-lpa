<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zend\Form\Element;
use Zend\Form\ElementInterface;
use Exception;

class GovUKZendFormExtension extends AbstractExtension
{
    private $blockMappings = [
        Element\Checkbox::class => 'form_input_checkbox',
        Element\Password::class => 'form_input_password',
        Element\Text::class     => 'form_input_text',
    ];

    public function getFunctions() : array
    {
        return [
            new TwigFunction('govuk_form_element', [$this, 'formElement'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * @param Environment $twigEnv
     * @param ElementInterface $element
     * @param string|null $label
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formElement(Environment $twigEnv, ElementInterface $element, string $label = null) : string
    {
        //  Check for a valid block mapping
        $eleClass = get_class($element);

        if (!isset($this->blockMappings[$eleClass])) {
            throw new Exception('Block mapping unavailable for ' . $eleClass);
        }

        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        if (!empty($label)) {
            $element->setLabel($label);
        }

        return $template->renderBlock($this->blockMappings[$eleClass], [
            'element' => $element,
        ]);
    }
}
