<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Service\Security\CSPNonce;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CSPMiddleware implements MiddlewareInterface
{
    public const string NONCE_ATTRIBUTE = 'csp-nonce';

    public function __construct(
        private bool $enforce,
        private string $reportUri,
        private CSPNonce $nonce,
        private string $authenticationDomain,
        private string $iapDomain,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $csp  = "default-src  'none';";
        $csp .= "script-src   'self' " .
            "https://www.googletagmanager.com https://www.google-analytics.com 'nonce-" . $this->nonce . "';";
        $csp .= "style-src    'self' 'nonce-" . $this->nonce . "';";
        $csp .= "font-src     'self';";
        $csp .= "manifest-src 'self';";
        $csp .= "connect-src  'self' " .
            'https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com;';

        $iapSrc = '';
        if (
            $request->getAttribute(RouteResult::class)->getMatchedRouteName() === 'lpa.view'
            || $request->getAttribute(RouteResult::class)->getMatchedRouteName() === 'view-lpa'
        ) {
            $iapSrc = 'data: ' . $this->iapDomain;
        }
        $csp .= "img-src 'self' https://*.google-analytics.com https://*.googletagmanager.com " . $iapSrc . ';';

        // instead of setting form-action to null to work around inconsistant Chrome/Firefox implementations
        // lets just work out if we need to add the OIDC domain for this request.
        $authenticationSrc = '';
        if ($request->getAttribute(RouteResult::class)->getMatchedRouteName() === 'home') {
            $authenticationSrc = $this->authenticationDomain;
        }
        $csp .= "form-action 'self' " . $authenticationSrc . ';';

        $csp .= 'report-uri ' . $this->reportUri . '/_csp;';

        return $handler->handle(
            $request->withAttribute(self::NONCE_ATTRIBUTE, $this->nonce)
        )->withAddedHeader(
            $this->enforce ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            $csp,
        );
    }
}
