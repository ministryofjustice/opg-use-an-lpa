<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateShareCode;
use Common\Entity\Lpa;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class CreateViewerCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use Session;
    use CsrfGuard;

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
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService)
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
        $form = new CreateShareCode($this->getCsrfGuard($request));

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $validated = $form->getData();

                $codeData = $this->viewerCodeService->createShareCode(
                    $identity,
                    $validated['lpa_token'],
                    $validated['org_name']
                );

                $lpa = $this->fetchLpa($identity, $validated['lpa_token']);

                return new HtmlResponse($this->renderer->render('actor::lpa-show-viewercode', [
                    'user'         => $user,
                    'actorToken'   => $validated['lpa_token'],
                    'code'         => $codeData['code'],
                    'expires'      => $codeData['expires'],
                    'organisation' => $codeData['organisation'],
                    'lpa'          => $lpa
                ]));
            }
        }

        // the lpa actor token is either a query parameter or a form value.
        // get it from the form if it doesn't exist as a parameter
        if (isset($request->getQueryParams()['lpa'])) {
            $form->setData(['lpa_token' => $request->getQueryParams()['lpa']]);
        }

        if (is_null($form->get('lpa_token')->getValue())) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $lpa = $this->fetchLpa($identity, $form->get('lpa_token')->getValue());

        return new HtmlResponse($this->renderer->render('actor::lpa-create-viewercode', [
            'user'       => $user,
            'lpa'        => $lpa,
            'actorToken' => $form->get('lpa_token')->getValue(),
            'form'       => $form
        ]));
    }

    private function fetchLpa(string $userToken, string $actorLpaToken): ?Lpa
    {
        $lpa = $this->lpaService->getLpaById($userToken, $actorLpaToken);

        //could it be null
        return $lpa;
    }
}