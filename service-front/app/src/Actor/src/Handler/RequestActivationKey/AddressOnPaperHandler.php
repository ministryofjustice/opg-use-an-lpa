<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Actor\Form\RequestActivationKey\AddressOnPaper;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActorRoleHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class AddressOnPaperHandler extends AbstractCleansingDetailsHandler
{
    private AddressOnPaper $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new AddressOnPaper($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->state($request)->addressOnPaper) {
            $this->form->setData(['address_on_paper_area' => $this->state($request)->addressOnPaper]);
        }
        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/address-on-paper',
            [
                'user'  => $this->user,
                'form'  => $this->form,
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $address = $this->form->getData()['address_on_paper_area'];

            $this->state($request)->addressOnPaper = $address;

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/address-on-paper', [
            'user' => $this->user,
            'form' => $this->form,
            'back' => $this->lastPage($this->state($request))
        ]));
    }

    public function nextPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.actor-role';

        return $state->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY
            ? 'lpa.add.donor-details'
            : 'lpa.add.contact-details';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.actor-address';
    }
}
