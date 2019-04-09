<?php

namespace Viewer\Handler\Initializer;

use Zend\Expressive\Template\TemplateRendererInterface;
use UnexpectedValueException;

/**
 * Getter and Setter, implementing the TemplatingSupportInterface.
 *
 * Class TemplatingSupportTrait
 * @package Viewer\Handler\Initializer
 */
trait TemplatingSupportTrait
{
    /**
     * @var TemplateRendererInterface
     */
    private $template;

    /**
     * @param TemplateRendererInterface $template
     */
    public function setTemplateRenderer(TemplateRendererInterface $template)
    {
        $this->template = $template;
    }

    /**
     * @return TemplateRendererInterface
     */
    public function getTemplateRenderer() : TemplateRendererInterface
    {

        if (!( $this->template instanceof TemplateRendererInterface )) {
            throw new UnexpectedValueException('TemplateRenderer not set');
        }

        return $this->template;
    }
}
