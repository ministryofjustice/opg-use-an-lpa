<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Context\UserContext;
use Common\Service\Lpa\LpaService;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
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
        $user  = $request->getAttribute(UserContext::class);
        $lpas  = $this->lpaService->getLpas($user, true);

        $hasDuplicates = array_reduce($lpas->getArrayCopy(), function ($duplicate, $lpaArray) {
            $uids = array_map(fn ($lpa) => $lpa->lpa->getUId(), $lpaArray);

            return $duplicate || count($uids) !== count(array_flip($uids));
        }, false);

        $hasActiveCodes = array_reduce($lpas->getArrayCopy(), function ($hasCodes, $lpa) {
            return $hasCodes || array_shift($lpa)->activeCodeCount > 0;
        }, false);

        $totalLpas = array_sum(array_map('count', $lpas->getArrayCopy())) ?? 0;

        return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'lpas'               => $lpas,
            'has_active_codes'   => $hasActiveCodes,
            'total_lpas'         => $totalLpas,
            'has_duplicate_lpas' => $hasDuplicates,
        ]));
    }
}
