<?php

declare(strict_types=1);

namespace Common\Middleware\ErrorHandling;

use Mezzio\Container\Psr17ResponseFactoryTrait;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Factory for the GoneHandler.
 */
class GoneHandlerFactory
{
    use Psr17ResponseFactoryTrait;

    /**
     * Invokes the creation of a GoneHandler.
     *
     * @param ContainerInterface $container The container interface.
     * @return GoneHandler Returns an instance of GoneHandler.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): GoneHandler
    {
        $renderer        = $container->get(TemplateRendererInterface::class);
        $responseFactory = $this->detectResponseFactory($container);

        return new GoneHandler($responseFactory, $renderer);
    }
}
