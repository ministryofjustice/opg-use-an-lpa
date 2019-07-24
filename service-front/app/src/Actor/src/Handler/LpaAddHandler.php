<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaAdd;
use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class LpaAddHandler
 * @package Actor\Handler
 */
class LpaAddHandler extends AbstractHandler
{
    /** @var AuthenticationInterface */
    private $authenticator;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator)
    {
        parent::__construct($renderer, $urlHelper);

        $this->authenticator = $authenticator;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new LpaAdd($guard);

        if ($request->getMethod() === 'POST') {

            //TODO

        }

        return new HtmlResponse($this->renderer->render('actor::lpa-add', [
            'form' => $form,
            'user' => $this->authenticator->authenticate($request)
        ]));
    }
}
