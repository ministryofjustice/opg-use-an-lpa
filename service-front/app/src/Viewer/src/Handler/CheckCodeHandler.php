<?php

declare(strict_types=1);

namespace Viewer\Handler;

use ArrayObject;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\Organisation;

/**
 * @codeCoverageIgnore
 */
class CheckCodeHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;
    use SessionTrait;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private LpaService $lpaService,
        private RateLimitService $failureRateLimiter,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception|\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code    = $this->getSession($request, 'session')->get('code');
        $surname = $this->getSession($request, 'session')->get('surname');
        $form    = new Organisation($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());
            if ($form->isValid()) {
                $session = $this->getSession($request, 'session');
                $session->set('organisation', $form->getData()['organisation']);
                return $this->redirectToRoute('view-lpa');
            }
        }

        if (isset($code)) {
            try {
                $lpa = $this->lpaService->getLpaByCode($code, $surname, null);

                if ($lpa instanceof ArrayObject) {
                    // Then we found a LPA for the given code
                    $expires = new DateTime($lpa->expires);
                    $status  = strtolower($lpa->lpa->getStatus());
                    if ($this->canDisplayLPA($status)) {
                        return new HtmlResponse($this->renderer->render(
                            'viewer::check-code-found',
                            [
                                'lpa'     => $lpa->lpa,
                                'expires' => $expires->format('Y-m-d'),
                                'form'    => $form,
                            ]
                        ));
                    }
                }
            } catch (ApiException $apiEx) {
                if ($apiEx->getCode() === StatusCodeInterface::STATUS_GONE) {
                    if ($apiEx->getMessage() === 'Share code cancelled') {
                        return new HtmlResponse($this->renderer->render('viewer::check-code-cancelled'));
                    } else {
                        return new HtmlResponse($this->renderer->render('viewer::check-code-expired'));
                    }
                }
            }

            $this->failureRateLimiter->limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));
            return new HtmlResponse($this->renderer->render(
                'viewer::check-code-not-found',
                [
                    'donor_last_name' => $surname,
                    'lpa_access_code' => $code,
                ]
            ));
        }

        //  We don't have a code so the session has timed out
        throw new SessionTimeoutException();
    }

    public function canDisplayLPA(string $status): bool
    {
        return $status === 'registered' || $status === 'cancelled' || $status === 'revoked';
    }
}
