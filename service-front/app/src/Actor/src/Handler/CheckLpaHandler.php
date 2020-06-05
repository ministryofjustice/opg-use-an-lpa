<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaConfirm;
use App\Service\User\UserService;
use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
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

    /** @var LpaService */
    private $lpaService;

    /**
     * LpaAddHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
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
                                'id'  => $identity,
                                'uId' => $referenceNumber
                            ]
                        );

                        if (!is_null($actorCode)) {
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

                $this->getLogger()->debug(
                    'Account with Id {id} has found an LPA with Id {uId} using their passcode',
                    [
                        'id'  => $identity,
                        'uId' => $referenceNumber
                    ]
                );

                if (!is_null($lpaData['lpa']) && (strtolower($lpaData['lpa']->getStatus()) === 'registered')) {
                    [$user, $userRole] = $this->resolveLpaData($lpaData, $dob);
//                    $userRole = $this->resolveLpaData($lpaData['lpa'], $dob);

                    $this->getLogger()->debug(
                        'Account with Id {id} identified as Role {role} on LPA with Id {uId}',
                        [
                            'id' => $identity,
                            'role' => $userRole,
                            'uId' => $referenceNumber
                        ]
                    );
                    return new HtmlResponse($this->renderer->render('actor::check-lpa', [
                        'form' => $form,
                        'lpa' => $lpaData['lpa'],
                        'user' => $user,
                        'userRole' => $userRole,
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
                        'user' => $user
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

                    //  Show LPA not found page
                    return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                        'user' => $user
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

    protected function resolveLpaData(ArrayObject $lpaData, string $dob): array
    {
        //  Check the logged in user role for this LPA
        $user = null;
        $userRole = null;
        $lpa = $lpaData['lpa'];
        $actor = $lpaData['actor'];
        $comparableDob = \DateTime::createFromFormat('!Y-m-d', $dob);

        if ($lpa instanceof Lpa && $actor['details'] instanceof CaseActor) {
            if (!is_null($lpa->getDonor()->getDob()) && $lpa->getDonor()->getDob() == $comparableDob) {
                $user = $lpa->getDonor();
                $userRole = 'Donor';
            } else {
                $user = $lpaData['actor']['details'];
                $userRole = 'Attorney';
            }
        }

        return [$user, $userRole];
    }
}
