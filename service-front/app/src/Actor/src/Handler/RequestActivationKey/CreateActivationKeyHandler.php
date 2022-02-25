<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Actor\Workflow\RequestActivationKey;
use Carbon\Carbon;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\AddOlderLpa};
use Common\Service\Email\EmailClient;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class CreateActivationKeyHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CreateActivationKeyHandler extends AbstractHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use Session;
    use CsrfGuard;
    use State;

    private EmailClient $emailClient;
    private AddOlderLpa $addOlderLpa;
    private LocalisedDate $localisedDate;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        AddOlderLpa $addOlderLpa,
        UrlHelper $urlHelper,
        EmailClient $emailClient,
        LocalisedDate $localisedDate
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
        $this->emailClient = $emailClient;
        $this->localisedDate = $localisedDate;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidRequestException|StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CreateNewActivationKey($this->getCsrfGuard($request));

        $user = $this->getUser($request);

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        $form->setData($request->getParsedBody());
        if ($form->isValid()) {
            $state = $this->state($request);

            $result = $this->addOlderLpa->confirm(
                $user->getIdentity(),
                $state->referenceNumber,
                $state->firstNames,
                $state->lastName,
                $state->dob,
                $state->postcode,
                $form->getData()['force_activation'] === 'yes'
            );

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::SUCCESS:
                    $letterExpectedDate = (new Carbon())->addWeeks(2);

                    $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                        $user->getDetails()['Email'],
                        (string) $state->referenceNumber,
                        strtoupper($state->postcode),
                        ($this->localisedDate)($letterExpectedDate)
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::send-activation-key-confirmation',
                            [
                                'date' => $letterExpectedDate,
                                'user' => $user,
                            ]
                        )
                    );
                case OlderLpaApiResponse::OLDER_LPA_NEEDS_CLEANSING:
                    $state->needsCleansing = true;
                    $state->actorUid = (int) $result->getData()['actor_id'];

                    return $this->redirectToRoute('lpa.add.contact-details');
            }
        }

        throw new InvalidRequestException('Invalid form');
    }

    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null
            || $this->state($request)->lastName === null
            || $this->state($request)->dob === null
            || $this->state($request)->postcode === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.dashboard';
    }

    public function lastPage(WorkflowState $state): string
    {
        return '';
    }
}
