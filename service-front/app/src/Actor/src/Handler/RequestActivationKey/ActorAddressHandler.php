<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorAbroadAddress;
use Actor\Form\RequestActivationKey\ActorAddress;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ActorAddressHandler extends AbstractCleansingDetailsHandler
{
    private ActorAddress|ActorAbroadAddress $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = $this->state($request)->liveInUK === 'Yes' ?
            new ActorAddress($this->getCsrfGuard($request)) :
            new ActorAbroadAddress($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->state($request)->liveInUK === 'Yes') {
            $this->form->setData(
                [
                    'actor_address_1'      => $this->state($request)->actorAddress1,
                    'actor_address_2'      => $this->state($request)->actorAddress2,
                    'actor_address_town'   => $this->state($request)->actorAddressTown,
                    'actor_address_county' => $this->state($request)->actorAddressCounty,
                ]
            );
        } else {
            $this->form->setData(
                [
                    'actor_abroad_address' => $this->state($request)->actorAbroadAddress,
                ]
            );
        }

        if ($this->state($request)->getActorAddressCheckResponse() !== null) {
            $this->form->setData(
                [
                    'actor_address_check_radio' => $this->state($request)->getActorAddressCheckResponse(),
                ]
            );
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address',
            [
                'user'       => $this->user,
                'form'       => $this->form->prepare(),
                'live_in_uk' => $this->state($request)->liveInUK,
                'back'       => $this->lastPage($this->state($request)),
                'postcode'   => $this->state($request)->postcode,
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            /**
             * @psalm-var array{
             *     actor_address_1: string,
             *     actor_address_2: string,
             *     actor_address_town: string,
             *     actor_address_county: string,
             *     actor_abroad_address: string,
             *     actor_address_check_radio: string
             * }
             */
            $postData = $this->form->getData();

            if ($this->state($request)->liveInUK === 'Yes') {
                $this->state($request)->actorAddress1      = $postData['actor_address_1'];
                $this->state($request)->actorAddress2      = $postData['actor_address_2'];
                $this->state($request)->actorAddressTown   = $postData['actor_address_town'];
                $this->state($request)->actorAddressCounty = $postData['actor_address_county'];
            } else {
                $this->state($request)->actorAbroadAddress = $postData['actor_abroad_address'];
            }
            $this->state($request)->actorAddressResponse = $postData['actor_address_check_radio'];
            $nextPageName                                = $this->nextPage($this->state($request));
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address',
            [
                'user'       => $this->user,
                'form'       => $this->form->prepare(),
                'live_in_uk' => $this->state($request)->liveInUK,
                'postcode'   => $this->state($request)->postcode,
                'back'       => $this->lastPage($this->state($request)),
            ]
        ));
    }

    public function nextPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        if ($this->hasFutureAnswersInState($state)) {
            return 'lpa.add.check-details-and-consent';
        }

        return $state->getActorAddressCheckResponse() === RequestActivationKey::ACTOR_ADDRESS_SELECTION_NO
            ? 'lpa.add.address-on-paper'
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
