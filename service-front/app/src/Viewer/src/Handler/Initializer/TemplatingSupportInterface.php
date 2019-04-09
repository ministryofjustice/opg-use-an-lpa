<?php

namespace Viewer\Handler\Initializer;

use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Declares Handler Middleware support template rendering.
 *
 * Interface TemplatingSupportInterface
 * @package Viewer\Handler\Initializer
 */
interface TemplatingSupportInterface
{
    /**
     * @param TemplateRendererInterface $template
     * @return mixed
     */
    public function setTemplateRenderer(TemplateRendererInterface $template);

    /**
     * @return TemplateRendererInterface
     */
    public function getTemplateRenderer() : TemplateRendererInterface;
}
