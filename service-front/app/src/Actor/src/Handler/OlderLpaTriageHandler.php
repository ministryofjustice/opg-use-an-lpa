<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Mezzio\Authentication\AuthenticationInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;

/**
 * Class OlderLpaTriageHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class OlderLpaTriageHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('actor::older-lpa-triage'));
    }
}
