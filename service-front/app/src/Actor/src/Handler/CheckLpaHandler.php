<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

/**
 * Class CheckLpaHandler
 * @package Actor\Handler
 */
class CheckLpaHandler extends AbstractHandler
{
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
        LpaService $lpaService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $session = $this->getSession($request,'session');

        $passcode = $session->get('passcode');
        $referenceNumber = $session->get('reference_number');
        $dob = $session->get('dob');

        if (isset($passcode) && isset($referenceNumber) && isset($dob)) {

            try {
                $lpa = $this->lpaService->getLpaByPasscode($passcode, $referenceNumber, $dob);

                if (!is_null($lpa)) {
                    //  Check the logged in user role for this LPA
                    $user = null;
                    $userRole = null;

                    if (!is_null($lpa->getDonor()->getDob()) && $lpa->getDonor()->getDob() == $dob) {
                        $user = $lpa->getDonor();
                        $userRole = 'Donor';
                    } elseif (!is_null($lpa->getAttorneys()) && is_iterable($lpa->getAttorneys())) {
                        //  Loop through the attorneys
                        foreach ($lpa->getAttorneys() as $attorney) {
                            if (!is_null($attorney->getDob()) && $attorney->getDob() == $dob) {
                                $user = $attorney;
                                $userRole = 'Attorney';
                            }
                        }
                    }

                    return new HtmlResponse($this->renderer->render('actor::check-lpa', [
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
}
