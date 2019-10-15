<?php

declare(strict_types=1);

namespace Actor\Handler;

use ArrayObject;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class ViewLpaSummaryHandler
 * @package Actor\Handler
 */
class ViewLpaSummaryHandler extends AbstractHandler
{
    use SessionTrait;
    use User;

    /**
     * @var LpaService
     */
    private $lpaService;

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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request,'session');

        $passcode = $session->get('passcode');
        $referenceNumber = $session->get('reference_number');
        $dob = $session->get('dob');

        if (isset($passcode) && isset($referenceNumber) && isset($dob)) {

            try {
                $lpaData = $this->lpaService->getLpaByPasscode($passcode, $referenceNumber, $dob);

                // The lpa comes back as two concurrent records. An actor which has been pulled from the
                // relevant attorneys section and the complete lpa record itself.
                $lpa = $lpaData['lpa'];

                if ($lpa instanceof ArrayObject) {
                    //  Check the logged in user role for this LPA
                    $user = null;
                    $userRole = null;

                    if (isset($lpa->donor->dob) && $lpa->donor->dob == $dob) {
                        $user = $lpa->donor;
                        $userRole = 'Donor';
                    } elseif (isset($lpa->attorneys) && is_iterable($lpa->attorneys)) {
                        //  Loop through the attorneys
                        foreach ($lpa->attorneys as $attorney) {
                            if (isset($attorney->dob) && $attorney->dob == $dob) {
                                $user = $attorney;
                                $userRole = 'Attorney';
                            }
                        }
                    }

                    return new HtmlResponse($this->renderer->render('actor::view-lpa-summary', [
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

//        $id = $this->getSession($request,'session')->get('reference_number');
//
//        if (!isset($id)) {
//            throw new SessionTimeoutException;
//        }
//
//        $lpa = $this->lpaService->getLpaById($id);

        return new HtmlResponse($this->renderer->render('actor::view-lpa-summary'));
    }
}