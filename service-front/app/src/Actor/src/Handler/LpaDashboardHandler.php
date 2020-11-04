<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Common\Service\Lpa\ViewerCodeService;

/**
 * Class LpaDashboardHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class LpaDashboardHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->viewerCodeService = $viewerCodeService;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpas = $this->lpaService->getLpas($identity);

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        if (count($lpas) === 0) {
            return new HtmlResponse($this->renderer->render('actor::lpa-blank-dashboard', [
                'user' => $user,
                'flash'=> $flash
            ]));
        }

        $totalCodes = 0;
        foreach ($lpas as $lpaKey => $lpaData) {
            $actorToken = $lpaData['user-lpa-actor-token'];

            $shareCodes = $this->viewerCodeService->getShareCodes(
                $identity,
                $actorToken,
                true
            );

            $lpas[$lpaKey]['activeCodeCount'] = $shareCodes['activeCodeCount'];
            $totalCodes += $shareCodes['activeCodeCount'];
            $lpas[$lpaKey]['actorActive'] = $lpaData['actor']['type'] === 'donor' || $lpaData['actor']['details']->getSystemStatus();
        }
        
        $lpas = $this->lpaService->sortLpasInOrder($lpas);
        $lpas = $this->lpaService->sortLpasByDonorSurname($lpas);
        
        return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'user'                      => $user,
            'lpas'                      => $lpas,
            'active_access_codes_count' => $totalCodes,
            'flash'                     => $flash
        ]));
    }
}
