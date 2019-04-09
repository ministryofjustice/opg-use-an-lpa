<?php

namespace Viewer\Handler\Initializer;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\ServiceManager\Initializer\InitializerInterface;

/**
 * Initialize Handler middleware with support for the UrlHelper.
 *
 * Class UrlHelperInitializer
 * @package Viewer\Handler\Initializer
 */
class UrlHelperInitializer implements InitializerInterface
{
    /**
     * @param ContainerInterface $container
     * @param object $instance
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof UrlHelperInterface && $container->has(UrlHelper::class)) {
            $instance->setUrlHelper($container->get(UrlHelper::class));
        }
    }
}
