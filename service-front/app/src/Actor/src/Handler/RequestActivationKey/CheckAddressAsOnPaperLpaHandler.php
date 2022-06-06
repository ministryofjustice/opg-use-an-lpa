<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckAddress;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class CheckAddressAsOnPaperLpaHandler extends AbstractCleansingDetailsHandler
{
    private CheckAddress $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckAddress($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->state($request)->getActorAddressCheckResponse() === 'Yes') {
            $this->form->setData(['actor_address_check_radio' => 'Yes']);
        } elseif ($this->state($request)->getActorAddressCheckResponse() === 'No') {
            $this->form->setData(['actor_address_check_radio' => 'No']);
        } elseif ($this->state($request)->getActorAddressCheckResponse() === 'Not sure') {
            $this->form->setData(['actor_address_check_radio' => 'Not sure']);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address-as-on-paper-lpa-check',
            [
                'user'  => $this->user,
                'form'  => $this->form,
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $selected = $this->form->getData()['actor_address_check_radio'];

            $this->state($request)->setActorAddressResponse($selected);

            $nextPageName = $this->nextPage($this->state($request));
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-address-as-on-paper-lpa-check',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
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
            : 'lpa.add.actor-address';
    }
}
