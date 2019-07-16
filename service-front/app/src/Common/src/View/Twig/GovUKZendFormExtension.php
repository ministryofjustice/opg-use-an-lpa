<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Actor\Form\Fieldset\Date;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zend\Form\Element;
use Zend\Form\ElementInterface;
use Zend\Form\FieldsetInterface;
use Exception;

class GovUKZendFormExtension extends AbstractExtension
{
    private $blockMappings = [
        Element\Checkbox::class => 'form_input_checkbox',
        Element\Password::class => 'form_input_password',
        Element\Text::class     => 'form_input_text',
        //  Fieldsets
        Date::class             => 'form_fieldset_date',
    ];

    /**
     * @return array
     */
    public function getFunctions() : array
    {
        return [
            new TwigFunction('govuk_form_element', [$this, 'formElement'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('govuk_form_fieldset', [$this, 'formFieldset'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * @param Environment $twigEnv
     * @param ElementInterface $element
     * @param array $options
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formElement(Environment $twigEnv, ElementInterface $element, array $options = []) : string
    {
        $blockMapping = $this->getBlockMapping($element);

        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        if (isset($options['label'])) {
            $element->setLabel($options['label']);
        }

        return $template->renderBlock($blockMapping,
            array_merge(
                [
                    'element' => $element,
                ],
                $options
            )
        );
    }

    /**
     * @param Environment $twigEnv
     * @param FieldsetInterface $fieldset
     * @param array $options
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formFieldset(Environment $twigEnv, FieldsetInterface $fieldset, array $options = []) : string
    {
        $blockMapping = $this->getBlockMapping($fieldset);

        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        if (isset($options['label'])) {
            $fieldset->setLabel($options['label']);
        }

        return $template->renderBlock($blockMapping,
            array_merge(
                [
                    'element' => $fieldset,
                ],
                $options
            )
        );
    }

    /**
     * @param ElementInterface $elementOrFieldset
     * @return string
     * @throws Exception
     */
    private function getBlockMapping(ElementInterface $elementOrFieldset) : string
    {
        //  Check for a valid block mapping
        $eleClass = get_class($elementOrFieldset);

        if (!isset($this->blockMappings[$eleClass])) {
            throw new Exception('Block mapping unavailable for ' . $eleClass);
        }

        return $this->blockMappings[$eleClass];
    }
}
