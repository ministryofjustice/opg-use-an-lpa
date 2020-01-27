<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Zend\Diactoros\Response\RedirectResponse;


/**
 * Class CancelCodeHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CancelCodeHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService,
        UrlHelper $urlHelper)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->viewerCodeService = $viewerCodeService;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $orgCode = $request->getQueryParams()['code'];
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;
        $lpa = $this->lpaService->getLpaById($identity, $actorLpaToken);

        $this->viewerCodeService->cancelShareCode(
            $identity,
            $actorLpaToken,
            $orgCode
        );

        //redirect to access code
       return new RedirectResponse($this->urlHelper->generate('lpa.access-codes',[],['lpa' => $actorLpaToken]));

    }
}