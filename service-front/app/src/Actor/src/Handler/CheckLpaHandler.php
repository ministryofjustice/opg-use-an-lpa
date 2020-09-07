<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaConfirm;
use Common\Exception\ApiException;
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
        $this->session = $this->getSession($request, 'session');

        $this->form = new LpaConfirm($this->getCsrfGuard($request));

        $this->user = $this->getUser($request);
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        $this->passcode = $this->session->get('passcode');
        $this->referenceNumber = $this->session->get('reference_number');
        $this->dob = $this->session->get('dob');

        if (!isset($this->identity) || !isset($this->passcode) || !isset($this->referenceNumber) || !isset($this->dob)) {
            // We don't have a code so the session has timed out
            // TODO this can be reached if the session is still perfectly valid but the lpa search/response
            //      failed in some way. Make this better.
            throw new SessionTimeoutException();
        }

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            case 'GET':
                return $this->handleGet($request);
            default:
                return $this->handleGet($request);
        }
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $lpaData = $this->lpaService->getLpaByPasscode(
                $this->identity,
                $this->passcode,
                $this->referenceNumber,
                $this->dob
            );

            $lpa = $lpaData['lpa'];
            $actor = $lpaData['actor']['details'];

            $this->getLogger()->debug(
                'Account with Id {id} has found an LPA with Id {uId} using their passcode',
                [
                    'id'  => $this->identity,
                    'uId' => $this->referenceNumber,
                ]
            );

            if (!is_null($lpa) && (strtolower($lpa->getStatus()) === 'registered')) {
                // Are we displaying Donor or Attorney user role
                $actorRole = (array_search($actor->getId(), $lpa->getDonor()->getIds()) !== false) ?
                    'Donor' :
                    'Attorney';

                $this->getLogger()->debug(
                    'Account with Id {id} identified as Role {role} on LPA with Id {uId}',
                    [
                        'id'   => $this->identity,
                        'role' => $actorRole,
                        'uId'  => $this->referenceNumber,
                    ]
                );

                // data to be used in flash message
                $this->session->set('donor_name', $lpa->getDonor()->getFirstname() . ' ' . $lpa->getDonor()->getSurname());
                $this->session->set('lpa_type', $lpa->getCaseSubtype() === 'hw' ? 'health and welfare' : 'property and finance');

                return new HtmlResponse($this->renderer->render('actor::check-lpa', [
                    'form' => $this->form,
                    'lpa' => $lpa,
                    'user' => $actor,
                    'userRole' => $actorRole,
                ]));

            } else {
                $this->getLogger()->debug(
                    'LPA with Id {uId} has {status} status and hence cannot be added',
                    [
                        'uId' => $this->referenceNumber,
                        'status' => $lpaData['lpa']->getStatus()
                    ]
                );
                //  Show LPA not found page
                return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                    'user'              => $this->user,
                    'dob'               => $this->dob,
                    'referenceNumber'   => $this->referenceNumber,
                    'passcode'          => $this->passcode
                ]));
            }
        } catch (ApiException $aex) {
            if ($aex->getCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
                $this->getLogger()->info(
                    'Account with Id {id} has failed to add an LPA to their account',
                    [
                        'id' => $this->identity,
                    ]
                );

                $this->rateLimitService->
                limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));
                //  Show LPA not found page
                return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                    'user'              => $this->user,
                    'dob'               => $this->dob,
                    'referenceNumber'   => $this->referenceNumber,
                    'passcode'          => $this->passcode
                ]));
            }

            throw $aex;
        }
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $actorCode = $this->lpaService->confirmLpaAddition(
                $this->identity,
                $this->passcode,
                $this->referenceNumber,
                $this->dob
            );

            $this->getLogger()->info(
                'Account with Id {id} has added LPA with Id {uId} to their account',
                [
                    'id' => $this->identity,
                    'uId' => $this->referenceNumber
                ]
            );

            if (!is_null($actorCode)) {

                /** @var FlashMessagesInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                $donor = $this->session->get('donor_name');
                $lpaType = $this->session->get('lpa_type');

                $flash->flash(self::ADD_LPA_FLASH_MSG, "You've added $donor's $lpaType LPA");

                return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
            }
        }
    }
}
