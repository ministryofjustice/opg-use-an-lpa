<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class ViewLpaSummaryHandler
 * @package Actor\Handler
 */
class ViewLpaSummaryHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use SessionTrait;
    use User;
    use CsrfGuard;

    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService,
        AuthenticationInterface $authenticator)
    {
        parent::__construct($renderer, $urlHelper);

        $this->lpaService = $lpaService;
        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {

//        $id = $this->getSession($request,'session')->get('reference_number');
//
//        if (!isset($id)) {
//            throw new SessionTimeoutException;
//        }
//
//        $lpa = $this->lpaService->getLpaById($id);

        return new HtmlResponse($this->renderer->render('actor::view-lpa-summary'));
    }
}