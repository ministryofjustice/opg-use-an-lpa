<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $lpas = $this->lpaService->getLpas($identity, true);

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        if (count($lpas) === 0) {
            return new HtmlResponse($this->renderer->render('actor::lpa-blank-dashboard', [
                'user' => $user,
                'flash' => $flash
            ]));
        }

        $hasActiveCodes = array_reduce($lpas->getArrayCopy(), function ($hasCodes, $lpa) {
            return $hasCodes ? true : array_shift($lpa)->activeCodeCount > 0;
        }, false);

        $totalLpas = array_sum(array_map('count', $lpas->getArrayCopy()));

        return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'user'             => $user,
            'lpas'             => $lpas,
            'has_active_codes' => $hasActiveCodes,
            'flash'            => $flash,
            'total_lpas'       => $totalLpas,
            'delete_lpa_env_var' => filter_var($_ENV['DELETE_LPA_FEATURE'], FILTER_VALIDATE_BOOL)
        ]));
    }
}
