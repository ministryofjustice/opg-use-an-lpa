<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestPostcode;
use Actor\Workflow\RequestActivationKey;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RequestActivationKeyHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class PostcodeHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestPostcode $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestPostcode($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if (($postcode = $this->state($request)->postcode) !== null) {
            $this->form->setData(
                [
                    'postcode' => $postcode
                ]
            );
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/postcode',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request))
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
            $this->state($request)->postcode = $postData['postcode'];

            return $this->redirectToRoute('lpa.check-answers');
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/postcode',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request))
                ]
            )
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null
            || $this->state($request)->dob === null;
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state */
        return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.date-of-birth';
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.check-answers';
    }
}
