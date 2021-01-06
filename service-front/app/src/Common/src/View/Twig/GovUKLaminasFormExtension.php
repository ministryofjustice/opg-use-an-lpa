<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Form\AbstractForm;
use Common\Form\Element\Email as CustomEmail;
use Common\Form\Fieldset\Date;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Laminas\Form\Element;
use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use Exception;

/**
 * Class GovUKLaminasFormExtension
 * @package Common\View\Twig
 */
class GovUKLaminasFormExtension extends AbstractExtension
{
    /**
     * Map the element types to blocks in the Twig partial template
     *
     * @var array
     */
    private $blockMappings = [
        Element\Checkbox::class => 'form_input_checkbox',
        Element\Csrf::class     => 'form_input_hidden',
        Element\Hidden::class   => 'form_input_hidden',
        Element\Password::class => 'form_input_password',
        CustomEmail ::class     => 'form_input_email',
        Element\Text::class     => 'form_input_text',
        Element\Radio::class    => 'form_input_radio',
        //  Fieldsets
        Date::class             => 'form_fieldset_date',
    ];

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('govuk_form_open', [$this, 'formOpen'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('govuk_form_close', [$this, 'formClose'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('govuk_form_element', [$this, 'formElement'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('govuk_form_fieldset', [$this, 'formFieldset'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * @param Environment $twigEnv
     * @param AbstractForm $form
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formOpen(Environment $twigEnv, AbstractForm $form)
    {
        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        return $template->renderBlock('form_open', [
            'form' => $form,
        ]);
    }

    /**
     * @param Environment $twigEnv
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formClose(Environment $twigEnv)
    {
        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        return $template->renderBlock('form_close');
    }

    /**
     * @param Environment $twigEnv
     * @param ElementInterface $element
     * @param array $options
     * @param FieldsetInterface|null $fieldset
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function formElement(Environment $twigEnv, ElementInterface $element, array $options = [], FieldsetInterface $fieldset = null): string
    {
        $elementBlock = $this->getBlockForElement($element);

        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        if (isset($options['label'])) {
            $element->setLabel($options['label']);
        }
        return $template->renderBlock(
            $elementBlock,
            array_merge(
                [
                    'element' => $element,
                    'fieldset' => $fieldset,
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
    public function formFieldset(Environment $twigEnv, FieldsetInterface $fieldset, array $options = []): string
    {
        $elementBlock = $this->getBlockForElement($fieldset);

        $template = $twigEnv->load('@partials/govuk_form.html.twig');

        if (isset($options['label'])) {
            $fieldset->setLabel($options['label']);
        }

        return $template->renderBlock(
            $elementBlock,
            array_merge(
                [
                    'fieldset' => $fieldset,
                ],
                $options
            )
        );
    }

    /**
     * @param ElementInterface $element
     * @return string
     * @throws Exception
     */
    private function getBlockForElement(ElementInterface $element): string
    {
        $eleClass = get_class($element);

        //  Check for a direct mapping
        if (isset($this->blockMappings[$eleClass])) {
            return $this->blockMappings[$eleClass];
        }

        //  Check for a mapping of a parent
        foreach ($this->blockMappings as $elementClass => $blockName) {
            if (is_subclass_of($eleClass, $elementClass)) {
                return $this->blockMappings[$elementClass];
            }
        }

        //  No mappings so throw an exception
        throw new Exception('Block mapping unavailable for ' . $eleClass);
    }
}
