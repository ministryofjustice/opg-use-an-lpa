<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{ CsrfGuardAware, UserAware};
use Actor\Form\RequestActivationKey\RequestDateOfBirth;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class DateOfBirthHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware
{
    private RequestDateOfBirth $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestDateOfBirth($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/date-of-birth', [
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
            $this->session->set(
                'dob',
                [
                    'day' => $postData['dob']['day'],
                    'month' => $postData['dob']['month'],
                    'year' => $postData['dob']['year']
                ]
            );
            $nextPageName = $this->getRouteNameFromAnswersInSession();
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/date-of-birth', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }
    protected function isSessionMissingPrerequisite(): bool
    {
        return !$this->session->has('opg_reference_number')
            || !$this->session->has('first_names');
    }

    protected function lastPage(): string
    {
        return 'lpa.your-name';
    }

    protected function nextPage(): string
    {
        return 'lpa.postcode';
    }
}
