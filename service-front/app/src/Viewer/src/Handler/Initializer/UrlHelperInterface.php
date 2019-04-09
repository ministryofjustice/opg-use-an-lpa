<?php

namespace Viewer\Handler\Initializer;

use Zend\Expressive\Helper\UrlHelper;

/**
 * Declares Handler Middleware support for UrlHelper
 *
 * Interface UrlHelperInterface
 * @package Viewer\Handler\Initializer
 */
interface UrlHelperInterface
{
    /**
     * @param UrlHelper $template
     * @return mixed
     */
    public function setUrlHelper(UrlHelper $template);

    /**
     * @return UrlHelper
     */
    public function getUrlHelper() : UrlHelper;
}
