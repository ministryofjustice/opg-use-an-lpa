<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\{AbstractHandler, UserAware};
use Common\Handler\Traits\User;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RequestActivationKeyInfoHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RequestActivationKeyInfoHandler extends AbstractHandler implements UserAware
{
    use User;

    private ?SessionInterface $session;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::request-activation-key-info', [
            'user' => $user,
        ]));
    }
}
