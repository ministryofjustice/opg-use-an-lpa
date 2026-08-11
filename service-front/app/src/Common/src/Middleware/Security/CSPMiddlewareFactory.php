<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Acpr\I18n\Translator;
use Common\Service\Security\CSPNonce;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Configuration for setting an appropriate CSP policy would be:
 *
 * <code>
 * 'csp' => [
 *     'enforce'               => true,
 *     'report_uri'            => 'https://my-report-uri',
 *     'authentication_domain' => 'https://*.my-auth-provider',
 *     'iap_domain'            => 'https://the-iap-bucket',
 * ],
 * </code>
 */
final class CSPMiddlewareFactory
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
            $config['application'],
            $config['csp']['authentication_domain'],
            $config['csp']['iap_domain'],
        );
    }
}
