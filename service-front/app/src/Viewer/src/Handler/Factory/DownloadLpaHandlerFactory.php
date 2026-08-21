<?php

declare(strict_types=1);

namespace Viewer\Handler\Factory;

use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfService;
use Common\Service\Security\RateLimitServiceFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Viewer\Handler\DownloadLpaHandler;

class DownloadLpaHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $rateLimitFactory = $container->get(RateLimitServiceFactory::class);

        return new DownloadLpaHandler(
            $container->get(LoggerInterface::class),
            $container->get(FeatureEnabled::class),
            $container->get(LpaService::class),
            $container->get(PdfService::class),
            $rateLimitFactory->factory('download_lpa'),
        );
    }
}
