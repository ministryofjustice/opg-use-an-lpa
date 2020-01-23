<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use DI\Factory\RequestedEntry;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class PdfServiceFactory
 *
 * @package Common\Service\Pdf
 */
class PdfServiceFactory
{
    public function __invoke(ContainerInterface $container, RequestedEntry $entityClass)
    {
        $config = $container->get('config');

        if (!array_key_exists('pdf', $config)) {
            throw new RuntimeException('PDF configuration missing');
        }

        if (!array_key_exists('uri', $config['pdf'])) {
            throw new RuntimeException('Missing API configuration: uri');
        }

        return new PdfService(
            $container->get(TemplateRendererInterface::class),
            $container->get(ClientInterface::class),
            $container->get(StylesService::class),
            $config['pdf']['uri'],
        );
    }
}
