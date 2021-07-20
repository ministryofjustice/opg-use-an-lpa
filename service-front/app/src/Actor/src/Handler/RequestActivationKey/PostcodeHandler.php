<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestPostcode;
use Common\Handler\{CsrfGuardAware, UserAware};
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RequestActivationKeyHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class PostcodeHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware
{
    private RequestPostcode $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestPostcode($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/postcode',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->getRouteNameFromAnswersInSession(true),
                ]
            )
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session and pass to the check your answers handler
            $this->session->set('postcode', $postData['postcode']);

            return $this->redirectToRoute('lpa.check-answers');
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/postcode',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->getRouteNameFromAnswersInSession(true),
                ]
            )
        );
    }

    protected function isSessionMissingPrerequisite(): bool
    {
        return !$this->session->has('opg_reference_number')
            || !$this->session->has('first_names')
            || !$this->session->has('dob');
    }

    protected function lastPage(): string
    {
        return 'lpa.date-of-birth';
    }

    protected function nextPage(): string
    {
        return 'lpa.check-answers';
    }
}
