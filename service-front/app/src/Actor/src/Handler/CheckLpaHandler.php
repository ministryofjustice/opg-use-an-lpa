<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\LpaConfirm;
use Actor\Workflow\AddLpa as AddLpaState;
use Common\Exception\RateLimitExceededException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResult;
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class CheckLpaHandler extends AbstractHandler implements CsrfGuardAware, UserAware, SessionAware, LoggerAware
{
    use CsrfGuard;
    use Logger;
    use SessionTrait;
    use State;
    use User;

    public const ADD_LPA_FLASH_MSG = 'add_lpa_flash_msg';

    private LpaConfirm $form;
    private ?string $identity;
    private ?SessionInterface $session;
    private AddLpaState $state;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
        private LpaService $lpaService,
        private RateLimitService $rateLimitService,
        private TranslatorInterface $translator,
        private AddLpa $addLpa,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     * @throws RateLimitExceededException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->session = $this->getSession($request, 'session');

        $this->form = new LpaConfirm($this->getCsrfGuard($request));

        $this->user     = $this->getUser($request);
        $this->identity = !is_null($this->user) ? $this->user->getIdentity() : null;

        $activation_key  = $this->state($request)->activationKey;
        $referenceNumber = $this->state($request)->lpaReferenceNumber;
        $dob             = $this->state($request)->dateOfBirth?->format('Y-m-d');

        if (!isset($this->identity)
            || !isset($activation_key)
            || !isset($referenceNumber)
            || !isset($dob)
        ) {
            $this->state($request)->reset();
            return $this->redirectToRoute('lpa.add-by-key');
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request, $activation_key, $referenceNumber, $dob),
            default => $this->handleGet($request, $activation_key, $referenceNumber, $dob),
        };
    }

    /**
     * @param  ServerRequestInterface $request
     * @param  string                 $activation_key
     * @param  string                 $referenceNumber
     * @param  string                 $dob
     * @return ResponseInterface
     * @throws RateLimitExceededException
     * @throws StateNotInitialisedException
     */
    public function handleGet(
        ServerRequestInterface $request,
        string $activation_key,
        string $referenceNumber,
        string $dob,
    ): ResponseInterface {
        $result = $this->addLpa->validate(
            $this->identity,
            $activation_key,
            $referenceNumber,
            $dob,
        );

        switch ($result->getResponse()) {
        case AddLpaApiResult::ADD_LPA_ALREADY_ADDED:
            $lpaAddedData = $result->getData();

            $this->state($request)->reset();
            return new HtmlResponse(
                $this->renderer->render(
                    'actor::lpa-already-added',
                    [
                            'user'       => $this->user,
                            'donor'      => $lpaAddedData->getDonor(),
                            'lpaType'    => $lpaAddedData->getCaseSubtype(),
                            'actorToken' => $lpaAddedData->getLpaActorToken(),
                        ]
                )
            );
        case AddLpaApiResult::ADD_LPA_NOT_ELIGIBLE:
        case AddLpaApiResult::ADD_LPA_NOT_FOUND:
            $this->rateLimitService
                ->limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));

            $this->state($request)->reset();
            return new HtmlResponse(
                $this->renderer->render(
                    'actor::lpa-not-found',
                    [
                            'user'            => $this->user,
                            'dob'             => $dob,
                            'referenceNumber' => $referenceNumber,
                            'activation_key'  => $activation_key,
                        ]
                )
            );
        case AddLpaApiResult::ADD_LPA_FOUND:
            $lpaData = $result->getData();

            $lpa   = $lpaData['lpa'];
            $actor = $lpaData['actor']['details'];

            $actorRole =
                $lpaData['actor']['type'] === 'donor'
                    ? 'Donor'
                    : ($lpaData['actor']['type'] === 'primary-attorney'
                        ? 'Attorney'
                        : 'Trust corporation'
                );

            $this->logger->debug(
                'Account with Id {id} identified as Role {role} on LPA with Id {uId}',
                [
                    'id'   => $this->identity,
                    'role' => $actorRole,
                    'uId'  => $lpa->getUId(),
                ]
            );

            // data to be used in flash message
            $this->session->set(
                'donor_name',
                $lpa->getDonor()->getFirstname() . ' ' . $lpa->getDonor()->getSurname()
            );
            $this->session->set(
                'lpa_type',
                $lpa->getCaseSubtype() === 'hw' ? 'health and welfare' : 'property and finance'
            );

            return new HtmlResponse(
                $this->renderer->render(
                    'actor::check-lpa',
                    [
                            'form'     => $this->form,
                            'lpa'      => $lpa,
                            'user'     => $actor,
                            'userRole' => $actorRole,
                        ]
                )
            );
        }

        // will never hit this but the method should return
        return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
    }

    /**
     * @throws StateNotInitialisedException
     */
    public function handlePost(
        ServerRequestInterface $request,
        string $activation_key,
        string $referenceNumber,
        string $dob,
    ): ResponseInterface {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $result = $this->addLpa->confirm(
                $this->identity,
                $activation_key,
                $referenceNumber,
                $dob
            );

            switch ($result->getResponse()) {
            case AddLpaApiResult::ADD_LPA_SUCCESS:
                /**
 * @var FlashMessagesInterface $flash 
*/
                $flash   = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                $donor   = $this->session->get('donor_name');
                $lpaType = $this->session->get('lpa_type');

                $this->logger->notice(
                    'Account with Id {id} added LPA of type {lpaType} to their account',
                    [
                        'event_code' => $lpaType === 'health and welfare'
                            ? EventCodes::ADDED_LPA_TYPE_HW
                            : EventCodes::ADDED_LPA_TYPE_PFA,
                        'lpaType'    => $lpaType,
                    ]
                );

                $message = $this->translator->translate(
                    "You've added %donor%'s %lpaType% LPA",
                    [
                        '%donor%'   => $donor,
                        '%lpaType%' => $lpaType,
                    ],
                    null,
                    'flashMessage'
                );
                $flash->flash(self::ADD_LPA_FLASH_MSG, $message);

                $this->state($request)->reset();
                return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
            case AddLpaApiResult::ADD_LPA_FAILURE:
                break;
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::lpa-not-found', [
                'user'            => $this->user,
                'dob'             => $dob,
                'referenceNumber' => $referenceNumber,
                'activation_key'  => $activation_key,
                ]
            )
        );
    }

    /**
     * @param  ServerRequestInterface $request
     * @return AddLpaState
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): AddLpaState
    {
        return $this->loadState($request, AddLpaState::class);
    }
}
