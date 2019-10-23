<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateShareCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class CreateShareCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
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
        if ($request->getMethod() === 'POST') {

            return $this->redirectToRoute('lpa.create-success');
        }

        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $form = new CreateShareCode($this->getCsrfGuard($request));

        return new HtmlResponse($this->renderer->render('actor::lpa-create-sharecode', [
            'user' => $this->getUser($request),
            'form' => $form
        ]));
    }
}