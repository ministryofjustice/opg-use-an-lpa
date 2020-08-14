<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ChangeDetailsHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ChangeDetailsHandler extends AbstractHandler implements UserAware
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
        // coming from the 'your details' page, there will not be an actorLpaToken
        $actorLpaToken = !empty($request->getQueryParams()) ? $request->getQueryParams()['lpa'] : 'null';

        $user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::change-details', [
            'actorToken' => $actorLpaToken,
            'user' => $user,
        ]));
    }
}
