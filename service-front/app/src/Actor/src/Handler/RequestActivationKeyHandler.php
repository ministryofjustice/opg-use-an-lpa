<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Actor\Form\RequestActivationKey;
use Common\Handler\Traits\User;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RequestActivationKeyHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

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
        $form = new RequestActivationKey($this->getCsrfGuard($request));

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key', [
            'user' => $this->getUser($request),
            'form' => $form->prepare()
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new RequestActivationKey($this->getCsrfGuard($request));
        $form->setData($request->getParsedBody());

        $session = $this->getSession($request, 'session');

        if ($form->isValid()) {
            $postData = $form->getData();

            //  Convert the date of birth
            $dobString = sprintf(
                '%s-%s-%s',
                $postData['dob']['year'],
                $postData['dob']['month'],
                $postData['dob']['day']
            );

            //  Set the data in the session and pass to the check your answers handler
            $session->set('opg_reference_number', $postData['opg_reference_number']);
            $session->set('first_names', $postData['first_names']);
            $session->set('last_name', $postData['last_name']);
            $session->set('dob', $dobString);
            $session->set('postcode', $postData['postcode']);

            return $this->redirectToRoute('lpa.check-answers');
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key', [
            'user' => $this->getUser($request),
            'form' => $form->prepare()
        ]));
    }
}
