<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Acpr\I18n\Translator;
use Common\Service\Security\CSPNonce;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Configuration for setting a default locale should look like the following:
 *
 * <code>
 * 'i18n' => [
 *     'default_locale' => 'en_GB',
 * ]
 * </code>
 */
class CSPMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CSPMiddleware
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (
            !empty(
                array_diff(
                    [
                        'enforce',
                        'report_uri',
                        'authentication_domain',
                        'iap_domain',
                    ],
                    array_keys($config['csp'])
                )
            )
        ) {
            throw new RuntimeException('CSP configuration value missing');
        }

        return new CSPMiddleware(
            $config['csp']['enforce'],
            $config['csp']['report_uri'],
            $container->get(CSPNonce::class),
            $config['csp']['authentication_domain'],
            $config['csp']['iap_domain'],
        );
    }
}
