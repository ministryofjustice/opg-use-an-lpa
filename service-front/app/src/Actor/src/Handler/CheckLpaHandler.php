<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaConfirm;
use App\Service\User\UserService;
use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
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
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

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

    /** @var LpaService */
    private $lpaService;

    /** @var RateLimitService */
    private $rateLimitService;

    /**
     * LpaAddHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     * @param RateLimitService $rateLimitService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        LoggerInterface $logger,
        RateLimitService $rateLimitService
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception|\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request, 'session');

        $form = new LpaConfirm($this->getCsrfGuard($request));

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $passcode = $session->get('passcode');
        $referenceNumber = $session->get('reference_number');
        $dob = $session->get('dob');

        if (isset($identity) && isset($passcode) && isset($referenceNumber) && isset($dob)) {
            try {
                if ($request->getMethod() === 'POST') {
                    $form->setData($request->getParsedBody());

                    if ($form->isValid()) {
                        $actorCode = $this->lpaService->confirmLpaAddition(
                            $identity,
                            $passcode,
                            $referenceNumber,
                            $dob
                        );

                        $this->getLogger()->info(
                            'Account with Id {id} has added LPA with Id {uId} to their account',
                            [
                                'id' => $identity,
                                'uId' => $referenceNumber
                            ]
                        );

                        if (!is_null($actorCode)) {
                            /** @var FlashMessagesInterface $flash */
                            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                            $donor = $session->get('donor_name');
                            $lpaType = $session->get('lpa_type');
                            $flash->flash(self::ADD_LPA_FLASH_MSG, "You've added $donor's $lpaType LPA");

                            return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
                        }
                    }
                }

                // is a GET or failed POST
                $lpaData = $this->lpaService->getLpaByPasscode(
                    $identity,
                    $passcode,
                    $referenceNumber,
                    $dob
                );

                $lpa = $lpaData['lpa'];
                $actor = $lpaData['actor']['details'];

                $this->getLogger()->debug(
                    'Account with Id {id} has found an LPA with Id {uId} using their passcode',
                    [
                        'id' => $identity,
                        'uId' => $referenceNumber
                    ]
                );

                if (!is_null($lpa) && (strtolower($lpa->getStatus()) === 'registered')) {
                    // Are we displaying Donor or Attorney user role
                    $actorRole = ($lpa->getDonor()->getId() === $actor->getId()) ?
                        'Donor' :
                        'Attorney';

                    $this->getLogger()->debug(
                        'Account with Id {id} identified as Role {role} on LPA with Id {uId}',
                        [
                            'id' => $identity,
                            'role' => $actorRole,
                            'uId' => $referenceNumber
                        ]
                    );

                    // data to be used in flash message
                    $session->set('donor_name', $lpa->getDonor()->getFirstname() . ' ' . $lpa->getDonor()->getSurname());
                    $session->set('lpa_type', $lpa->getCaseSubtype() === 'hw' ? 'health and welfare' : 'property and finance');

                    return new HtmlResponse($this->renderer->render('actor::check-lpa', [
                        'form' => $form,
                        'lpa' => $lpa,
                        'user' => $actor,
                        'userRole' => $actorRole,
                    ]));
                } else {
                    $this->getLogger()->debug(
                        'LPA with Id {uId} has {status} status and hence cannot be added',
                        [
                            'uId' => $referenceNumber,
                            'status' => $lpaData['lpa']->getStatus()
                        ]
                    );
                    //  Show LPA not found page
                    return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                        'user'              => $user,
                        'dob'               => $dob,
                        'referenceNumber'   => $referenceNumber,
                        'passcode'          => $passcode
                    ]));
                }
            } catch (ApiException $aex) {
                if ($aex->getCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
                    $this->getLogger()->info(
                        'Account with Id {id} has failed to add an LPA to their account',
                        [
                            'id' => $identity
                        ]
                    );

                    $this->rateLimitService->
                        limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));
                    //  Show LPA not found page
                    return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                        'user' => $user,
                        'dob'               => $dob,
                        'referenceNumber'   => $referenceNumber,
                        'passcode'          => $passcode
                    ]));
                }

                throw $aex;
            }
        }

        // We don't have a code so the session has timed out
        // TODO this can be reached if the session is still perfectly valid but the lpa search/response
        //      failed in some way. Make this better.
        throw new SessionTimeoutException();
    }
}
