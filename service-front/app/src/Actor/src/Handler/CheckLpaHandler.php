<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaConfirm;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class CheckLpaHandler
 * @package Actor\Handler
 */
class CheckLpaHandler extends AbstractHandler implements CsrfGuardAware, UserAware
{
    use CsrfGuard;
    use SessionTrait;
    use User;

    /** @var LpaService */
    private $lpaService;

    /**
     * LpaAddHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     * @param LpaService $lpaService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService
    ) {
        parent::__construct($renderer, $urlHelper);

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

                        if (!is_null($actorCode)) {
                            return new RedirectResponse($this->urlHelper->generate('lpa.dashboard'));
                        }
                    }
                }

                // is a GET or failed POST
                $lpa = $this->lpaService->getLpaByPasscode(
                    $identity,
                    $passcode,
                    $referenceNumber,
                    $dob
                );

                if (!is_null($lpa)) {
                    list($user, $userRole) = $this->resolveLpaData($lpa, $dob);

                    return new HtmlResponse($this->renderer->render('actor::check-lpa', [
                        'form'     => $form,
                        'lpa'      => $lpa,
                        'user'     => $user,
                        'userRole' => $userRole,
                    ]));
                }
            } catch (ApiException $aex) {
                if ($aex->getCode() == StatusCodeInterface::STATUS_NOT_FOUND) {
                    //  Show LPA not found page
                    return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                        'user' => $this->getUser($request)
                    ]));
                } else {
                    throw $aex;
                }
            }
        }

        // We don't have a code so the session has timed out
        // TODO this can be reached if the session is still perfectly valid but the lpa search/response
        //      failed in some way. Make this better.
        throw new SessionTimeoutException();
    }

    protected function resolveLpaData(Lpa $lpa, string $dob): array
    {
        //  Check the logged in user role for this LPA
        $user = null;
        $userRole = null;
        $comparableDob = \DateTime::createFromFormat('!Y-m-d', $dob);

        if (!is_null($lpa->getDonor()->getDob()) && $lpa->getDonor()->getDob() == $comparableDob) {
            $user = $lpa->getDonor();
            $userRole = 'Donor';
        } elseif (!is_null($lpa->getAttorneys()) && is_iterable($lpa->getAttorneys())) {
            //  Loop through the attorneys
            /** @var CaseActor $attorney */
            foreach ($lpa->getAttorneys() as $attorney) {
                if (!is_null($attorney->getDob()) && $attorney->getDob() == $comparableDob) {
                    $user = $attorney;
                    $userRole = 'Attorney';
                }
            }
        }

        return [$user, $userRole];
    }
}
