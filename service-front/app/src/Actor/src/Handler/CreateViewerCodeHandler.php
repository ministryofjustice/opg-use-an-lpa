<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateShareCode;
use App\Service\Lpa\LpaService;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
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

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        ViewerCodeService $viewerCodeService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
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

                // we successfully created a code. Set the details in the session and
                // bounce the request onwards.
                $session = $this->getSession($request, 'session');
                $session->set('code', $codeData['code']);
                $session->set('expires', $codeData['expires']);
                $session->set('organisation', $codeData['organisation']);

                return $this->redirectToRoute('lpa.create-code');
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

        return new HtmlResponse($this->renderer->render('actor::lpa-create-viewercode', [
            'user'       => $user,
            'actorToken' => $form->get('lpa_token')->getValue(),
            'form'       => $form
        ]));
    }
}