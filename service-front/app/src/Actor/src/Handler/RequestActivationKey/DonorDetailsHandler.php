<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\DonorDetails;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Handler\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class DonorDetailsHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class DonorDetailsHandler extends AbstractCleansingDetailsHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new DonorDetails($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/donor-details', [
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

            $this->session->set('donor_first_names', $postData['donor_first_names']);
            $this->session->set('donor_last_name', $postData['donor_last_name']);
            $this->session->set(
                'donor_dob',
                [
                    'day' => $postData['donor_dob']['day'],
                    'month' => $postData['donor_dob']['month'],
                    'year' => $postData['donor_dob']['year']
                ]
            );
        }
        $nextPageName = $this->getRouteNameFromAnswersInSession();
        return $this->redirectToRoute($nextPageName);
    }

    public function isMissingPrerequisite(): bool
    {
        return !$this->session->has('opg_reference_number')
            || !$this->session->has('first_names')
            || !$this->session->has('last_name')
            || !$this->session->has('dob')
            || !$this->session->has('postcode')
            || !$this->session->has('actor_role');
    }

    public function nextPage(): string
    {
        return 'lpa.add.contact-details';
    }

    public function lastPage(): string
    {
        return 'lpa.add.actor-role';
    }
}
