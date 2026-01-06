<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\SystemMessage\SystemMessageService;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class LpaDashboardHandler extends AbstractHandler implements UserAware
{
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private LpaService $lpaService,
        private ViewerCodeService $viewerCodeService,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user     = $this->getUser($request);
        $identity = $user?->getIdentity();

        $lpas = $this->lpaService->getLpas($identity, true);

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $hasActiveCodes = array_reduce($lpas->getArrayCopy(), function ($hasCodes, $lpa) {
            return $hasCodes || array_shift($lpa)->activeCodeCount > 0;
        }, false);

//        $hasDuplicates = array_reduce($lpas->getArrayCopy(), function ($duplicate, $lpaArray) {
//            $uids = array_map(fn ($lpa) => $lpa->lpa->getUId(), $lpaArray);
//
//            return $duplicate || count($uids) !== count(array_flip($uids));
//        }, false);

        $totalLpas = array_sum(array_map('count', $lpas->getArrayCopy())) ?? 0;

        $systemMessages = $this->systemMessageService->getMessages();

        return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'user'               => $user,
            'lpas'               => $lpas,
            'has_active_codes'   => $hasActiveCodes,
            'has_duplicate_lpas' => true,
            'flash'              => $flash,
            'total_lpas'         => $totalLpas,
            'en_message'         => $systemMessages['use/en'] ?? null,
            'cy_message'         => $systemMessages['use/cy'] ?? null,
        ]));
    }
}
