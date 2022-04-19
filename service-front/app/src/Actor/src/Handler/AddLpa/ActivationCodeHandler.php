<?php

declare(strict_types=1);

namespace Actor\Handler\AddLpa;

use Actor\Form\AddLpa\ActivationCode;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class LpaAddHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ActivationCodeHandler extends AbstractAddLpaHandler
{
    use CsrfGuard;
    use SessionTrait;
    use User;

    private ActivationCode $form;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActivationCode($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'passcode' => $this->state($request)->activationCode,
            ]
        );

        return new HtmlResponse(
            $this->renderer->render('actor::add-lpa/activation-code', [
                'form' => $this->form->prepare(),
                'user' => $this->getUser($request),
                'back' => $this->lastPage($this->state($request)),
            ])
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            //  Attempt to retrieve an LPA using the form data
            $postData = $this->form->getData();
            $this->state($request)->activationCode = $postData['passcode'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }
        return new HtmlResponse(
            $this->renderer->render('actor::add-lpa/activation-code', [
                'form' => $this->form->prepare(),
                'user' => $this->getUser($request),
                'back' => $this->lastPage($this->state($request)),
            ])
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.add-by-code.date-of-birth';
    }

    public function lastPage(WorkflowState $state): string
    {
        return 'lpa.dashboard';
    }
}
