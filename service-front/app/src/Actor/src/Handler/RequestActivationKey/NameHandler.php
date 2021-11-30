<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestNames;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

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
        $this->form->setData(
            [
                'first_names' => $this->state()->firstNames,
                'last_name' => $this->state()->lastName
            ]
        );

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

            //  Set the data in the state and pass to the check your answers handler
            $this->state()->firstNames = $postData['first_names'];
            $this->state()->lastName = $postData['last_name'];

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
        return $this->state()->referenceNumber !== null;
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
