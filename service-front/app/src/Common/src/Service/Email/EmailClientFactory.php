<?php

declare(strict_types=1);

namespace Common\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Class EmailClientFactory
 * @package Common\Service\Email
 */
class EmailClientFactory
{
    /**
     * @param ContainerInterface $container
     * @return EmailClient
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['notify']['api']['key'])) {
            throw new RuntimeException('Missing notify API key');
        }

        $notifyClient = new NotifyClient([
            'apiKey'     => $config['notify']['api']['key'],
            'httpClient' => $container->get(ClientInterface::class),
        ]);

        $urlHelper = $container->get(UrlHelper::class);

        return new EmailClient($notifyClient, $urlHelper);
    }
}
