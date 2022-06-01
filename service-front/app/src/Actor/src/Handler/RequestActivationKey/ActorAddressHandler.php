<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorAddress;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ActorAddressHandler extends AbstractCleansingDetailsHandler
{
    private ActorAddress $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActorAddress($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'actor_address_1' => $this->state($request)->actorAddress1,
                'actor_address_2' => $this->state($request)->actorAddress2,
                'actor_address_town' => $this->state($request)->actorAddressTown,
            ]
        );

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address',
            [
                'user'     => $this->user,
                'form'     => $this->form->prepare(),
                'postcode' => $this->state($request)->postcode,
                'back'     => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->actorAddress1 = $postData['actor_address_1'];
            $this->state($request)->actorAddress2 = $postData['actor_address_2'];
            $this->state($request)->actorAddressTown = $postData['actor_address_town'];

            $nextPageName = $this->nextPage($this->state($request));
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'postcode' => $this->state($request)->postcode,
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function nextPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.actor-role';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.check-answers';
    }
}
