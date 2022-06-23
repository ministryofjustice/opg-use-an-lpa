<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckDetailsAndConsent;
use Actor\Workflow\RequestActivationKey;
use Carbon\Carbon;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Email\EmailClient;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\CleanseLpa;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Workflow\State;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use DateTimeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Twig\Environment;

/**
 * Class CheckDetailsAndConsentHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class CheckDetailsAndConsentHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    LoggerAware,
    WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;
    use State;

    private CheckDetailsAndConsent $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;

    /** @var array<string, int|string|bool|DateTimeInterface|array|null>  */
    private array $data;

    private CleanseLpa $cleanseLPA;
    private EmailClient $emailClient;
    private LocalisedDate $localisedDate;
    private Environment $environment;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        CleanseLpa $cleanseLpa,
        EmailClient $emailClient,
        LocalisedDate $localisedDate,
        Environment $environment
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->cleanseLPA = $cleanseLpa;
        $this->emailClient = $emailClient;
        $this->localisedDate = $localisedDate;
        $this->environment = $environment;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckDetailsAndConsent($this->getCsrfGuard($request));

        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        $this->data = [
            'email' => $this->user->getDetail('email'),
        ];

        $state = $this->state($request);
        $state->noTelephone
            ? $this->data['no_phone']  = $state->noTelephone
            : $this->data['telephone'] = $state->telephone;

        if (!$state->needsCleansing && $state->actorUid === null) {
            $this->data['actor_role'] = $state->getActorRole();

            if ($state->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
                $this->data['donor_first_names'] = $state->donorFirstNames;
                $this->data['donor_last_name']   = $state->donorLastName;
                $this->data['donor_dob']         = $state->donorDob;
            }

            if ($state->getActorRole() === RequestActivationKey::ACTOR_DONOR) {
                $this->data['attorney_first_names'] = $state->attorneyFirstNames;
                $this->data['attorney_last_name']   = $state->attorneyLastName;
                $this->data['attorney_dob']         = $state->attorneyDob;
            }
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/check-details-and-consent',
            [
                'user' => $this->user,
                'form' => $this->form,
                'data' => $this->data
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $state = $this->state($request);

            $this->data['first_names']           = $state->firstNames;
            $this->data['last_name']             = $state->lastName;
            $this->data['dob']                   = $state->dob;
            $this->data['actor_id']              = $state->actorUid;
            $this->data['actor_current_address'] = array_filter([
                $state->actorAddress1,
                $state->actorAddress2,
                $state->actorAddressTown,
                $state->actorAddressCounty,
                $state->postcode
            ]);
            $this->data['address_on_paper']      = $state->addressOnPaper;

            $txtRenderer = new TwigRenderer($this->environment, 'txt.twig');
            $additionalInfo = $txtRenderer->render('actor::request-cleanse-note', ['data' => $this->data]);

            $this->getLogger()->notice(
                'User {id} has requested an activation key for their {match} OOLPA ' .
                'and provided the following contact information: {role}, {phone}',
                [
                    'id'    => $this->user->getIdentity(),
                    'role'  => $state->getActorRole() === RequestActivationKey::ACTOR_DONOR ?
                        EventCodes::OOLPA_KEY_REQUESTED_FOR_DONOR :
                        EventCodes::OOLPA_KEY_REQUESTED_FOR_ATTORNEY,
                    'phone' => $state->telephone !== null ?
                        EventCodes::OOLPA_PHONE_NUMBER_PROVIDED :
                        EventCodes::OOLPA_PHONE_NUMBER_NOT_PROVIDED,
                    'match' => $this->data['actor_id'] === null ? 'part match' : 'full match'
                ]
            );

            $result = $this->cleanseLPA->cleanse(
                $this->user->getIdentity(),
                $state->referenceNumber,
                $additionalInfo,
                $state->actorUid
            );

            $letterExpectedDate = (new Carbon())->addWeeks(6);

            if ($result->getResponse() === OlderLpaApiResponse::SUCCESS) {
                $this->emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
                    $this->data['email'],
                    (string) $state->referenceNumber,
                    ($this->localisedDate)($letterExpectedDate)
                );

                return new HtmlResponse(
                    $this->renderer->render(
                        'actor::activation-key-request-received',
                        [
                            'user' => $this->user,
                            'date' => $letterExpectedDate
                        ]
                    )
                );
            }

            $this->getLogger()->alert(
                'LPA cleanse request to our API did not return expected response in ' . __METHOD__
            );
            throw new RuntimeException('LPA cleanse request to our API did not return expected response');
        }

        $this->getLogger()->alert('Invalid CSRF when submitting to ' . __METHOD__);
        throw new InvalidRequestException('Invalid CSRF when submitting form');
    }

    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        $alwaysRequired = $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null
            || $this->state($request)->lastName === null
            || $this->state($request)->dob === null
            || $this->state($request)->postcode === null
            || (
                $this->state($request)->telephone === null
                && $this->state($request)->noTelephone === null
            );

        // If lpa is a full match and not cleansed then we need to short circuit the pre-requisite check
        if (!$alwaysRequired && $this->state($request)->needsCleansing) {
            return $this->state($request)->actorUid === null; // isMissing equals false if actorUid present
        }

        $alwaysRequired = $alwaysRequired || $this->state($request)->getActorRole() === null
            || $this->state($request)->actorAddress1 === null
            || $this->state($request)->actorAddressTown === null;

        if ($this->state($request)->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
            return $alwaysRequired
                || $this->state($request)->donorFirstNames === null
                || $this->state($request)->donorLastName === null
                || $this->state($request)->donorDob === null;
        }

        return $alwaysRequired;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.add-by-paper';
    }

    public function lastPage(WorkflowState $state): string
    {
        return 'lpa.add.contact-details';
    }
}
