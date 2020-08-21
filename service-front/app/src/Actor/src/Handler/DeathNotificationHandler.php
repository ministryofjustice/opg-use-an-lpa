<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Common\Handler\UserAware;

/**
 * Class DeathNotificationHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class DeathNotificationHandler extends AbstractHandler implements UserAware
{
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = !empty($request->getQueryParams()) ? $request->getQueryParams()['lpa'] : 'null';

        $user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::death-notification', [
            'actorToken' => $actorLpaToken,
            'user' => $user
        ]));
    }
}
