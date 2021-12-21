<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{CsrfGuardAware, UserAware, WorkflowStep};
use Actor\Form\RequestActivationKey\RequestNames;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class NameHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestNames $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestNames($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session and pass to the check your answers handler
            $this->session->set('first_names', str_replace(['‘', '’'], "'", $postData['first_names']));
            $this->session->set('last_name', str_replace(['‘', '’'], "'", $postData['last_name']));

            $nextPageName = $this->getRouteNameFromAnswersInSession();
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function isMissingPrerequisite(): bool
    {
        return !$this->session->has('opg_reference_number');
    }

    public function nextPage(): string
    {
        return 'lpa.date-of-birth';
    }

    public function lastPage(): string
    {
        return 'lpa.add-by-paper';
    }
}
