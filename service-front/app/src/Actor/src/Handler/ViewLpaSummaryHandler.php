<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ViewLpaSummaryHandler
 * @package Actor\Handler
 */
class ViewLpaSummaryHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * @var LpaService
     */
    private $lpaService;

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
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        //  var_dump($actorLpaToken);

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        // var_dump($user);
        //  var_dump($lpaData);
        // var_dump("no lpa data");
        //  die;

        if (count($lpaData) === 0) {
            $lpas = $this->lpaService->getLpas($identity, true);

            if (count($lpas) === 0) {
                return new HtmlResponse(
                    $this->renderer->render(
                        'actor::lpa-blank-dashboard',
                        [
                            'user' => $user
                        ]
                    )
                );
            }

            $hasActiveCodes = array_reduce(
                $lpas->getArrayCopy(),
                function ($hasCodes, $lpa) {
                    return $hasCodes ? true : array_shift($lpa)->activeCodeCount > 0;
                },
                false
            );

            $totalLpas = array_sum(array_map('count', $lpas->getArrayCopy()));

            /** @var FlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            return new HtmlResponse(
                $this->renderer->render(
                    'actor::lpa-dashboard',
                    [
                        'user'              => $user,
                        'lpas'              => $lpas,
                        'has_active_codes'  => $hasActiveCodes,
                         'flash'            => $flash,
                        'total_lpas'        => $totalLpas
                    ]
                )
            );
        }


        return new HtmlResponse(
            $this->renderer->render(
                'actor::view-lpa-summary',
                [
                    'actorToken' => $actorLpaToken,
                    'user' => $user,
                    'lpa' => $lpaData->lpa,
                    'actor' => $lpaData->actor,
                ]
            )
        );
    }
}
