<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\LpaConfirm;
use Common\Exception\RateLimitExceededException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResponse;
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
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
use ArrayObject;

/**
 * Class CheckLpaHandler
 *
 * @package Actor\Handler
 *
 * @codeCoverageIgnore
 */
class CheckLpaHandler extends AbstractHandler implements CsrfGuardAware, UserAware, LoggerAware
{
    use CsrfGuard;
    use SessionTrait;
    use User;
    use Logger;

    public const ADD_LPA_FLASH_MSG = 'add_lpa_flash_msg';

    private LpaConfirm $form;
    private ?string $identity;
    private LpaService $lpaService;
    private RateLimitService $rateLimitService;
    private ?SessionInterface $session;
    private TranslatorInterface $translator;
    private ?UserInterface $user;
    private AddLpa $addLpa;

    /**
     * LpaAddHandler constructor.
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper                 $urlHelper
     * @param AuthenticationInterface   $authenticator
     * @param LpaService                $lpaService
     * @param LoggerInterface           $logger
     * @param RateLimitService          $rateLimitService
     * @param TranslatorInterface       $translator
     * @param AddLpa                    $addLpa
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        LoggerInterface $logger,
        RateLimitService $rateLimitService,
        TranslatorInterface $translator,
        AddLpa $addLpa
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->rateLimitService = $rateLimitService;
        $this->translator = $translator;
        $this->addLpa = $addLpa;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception|\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->session = $this->getSession($request, 'session');

        $this->form = new LpaConfirm($this->getCsrfGuard($request));

        $this->user = $this->getUser($request);
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        $passcode = $this->session->get('passcode');
        $referenceNumber = $this->session->get('reference_number');
        $dob = $this->session->get('dob');

        if (
            !isset($this->identity)
            || !isset($passcode)
            || !isset($referenceNumber)
            || !isset($dob)
        ) {
            // We don't have a code so the session has timed out
            // TODO this can be reached if the session is still perfectly valid but the lpa search/response
            //      failed in some way. Make this better.
            throw new SessionTimeoutException();
        }

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request, $passcode, $referenceNumber, $dob);
            default:
                return $this->handleGet($request, $passcode, $referenceNumber, $dob);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $passcode
     * @param string                 $referenceNumber
     * @param string                 $dob
     *
     * @return ResponseInterface
     * @throws RateLimitExceededException
     */
    public function handleGet(
        ServerRequestInterface $request,
        string $passcode,
        string $referenceNumber,
        string $dob
    ): ResponseInterface {
        $result = $this->addLpa->validate(
            $this->identity,
            $passcode,
            $referenceNumber,
            $dob
        );

        switch ($result->getResponse()) {
            case AddLpaApiResponse::ADD_LPA_ALREADY_ADDED:
                $lpaAddedData = $result->getData();
                return new HtmlResponse(
                    $this->renderer->render(
                        'actor::lpa-already-added',
                        [
                            'user' => $this->user,
                            'lpa' => $lpaAddedData['lpa'],
                            'actorToken' => $lpaAddedData['user-lpa-actor-token']
                        ]
                    )
                );
            case AddLpaApiResponse::ADD_LPA_NOT_ELIGIBLE:
            case AddLpaApiResponse::ADD_LPA_NOT_FOUND:
                $this->rateLimitService->
                limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));
                return new HtmlResponse(
                    $this->renderer->render(
                        'actor::lpa-not-found',
                        [
                            'user' => $this->user,
                            'dob' => $dob,
                            'referenceNumber' => $referenceNumber,
                            'passcode' => $passcode
                        ]
                    )
                );
            case AddLpaApiResponse::ADD_LPA_FOUND:
                $lpaData = $result->getData();
                $lpa = $lpaData['lpa'];
                $actor = $lpaData['actor']['details'];
                $actorRole = ($lpaData['actor']['type'] === 'donor') ? 'Donor' : 'Attorney';

                $this->logger->debug(
                    'Account with Id {id} identified as Role {role} on LPA with Id {uId}',
                    [
                        'id' => $this->identity,
                        'role' => $actorRole,
                        'uId' => $lpa->getUId(),
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
                            'form' => $this->form,
                            'lpa' => $lpa,
                            'user' => $actor,
                            'userRole' => $actorRole,
                        ]
                    )
                );
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $passcode
     * @param string                 $referenceNumber
     * @param string                 $dob
     *
     * @return ResponseInterface
     */
    public function handlePost(
        ServerRequestInterface $request,
        string $passcode,
        string $referenceNumber,
        string $dob
    ): ResponseInterface {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $result = $this->addLpa->confirm(
                $this->identity,
                $passcode,
                $referenceNumber,
                $dob
            );

            switch ($result->getResponse()) {
                case AddLpaApiResponse::ADD_LPA_SUCCESS:
                    /** @var FlashMessagesInterface $flash */
                    $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                    $donor = $this->session->get('donor_name');
                    $lpaType = $this->session->get('lpa_type');

                    $message = $this->translator->translate(
                        "You've added %donor%'s %lpaType% LPA",
                        [
                            '%donor%' => $donor,
                            '%lpaType%' => $lpaType
                        ],
                        null,
                        'flashMessage'
                    );
                    $flash->flash(self::ADD_LPA_FLASH_MSG, $message);

                    return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
                case AddLpaApiResponse::ADD_LPA_FAILURE:
                    break;
            }
        }

        return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
            'user'              => $this->user,
            'dob'               => $dob,
            'referenceNumber'   => $referenceNumber,
            'passcode'          => $passcode
        ]));
    }
}
