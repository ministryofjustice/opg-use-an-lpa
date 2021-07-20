<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Actor\Form\RequestActivationKey\RequestNames;
use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Handler\Traits\User;
use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ReferenceNumberHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class ReferenceNumberHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware
{
    private RequestReferenceNumber $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestReferenceNumber($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getQueryParams()['clearSession']) {
            $this->clearSession();
        }

        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/reference-number', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this-> getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session and pass to the check your answers handler
            $this->session->set('opg_reference_number', $postData['opg_reference_number']);

            $nextPageName = $this->getRouteNameFromAnswersInSession();
            return $this->redirectToRoute($nextPageName);
        }
        return new HtmlResponse($this->renderer->render('actor::request-activation-key/reference-number', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    protected function nextPage(): string
    {
        return 'lpa.your-name';
    }

    protected function lastPage(): string
    {
        return 'lpa.add-by-paper-information';
    }

    private function clearSession()
    {
        $this->session->unset('postcode');
        $this->session->unset('first_names');
        $this->session->unset('last_name');
        $this->session->unset('dob');
        $this->session->unset('opg_reference_number');
    }
}
