<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\InstAndPrefImagesService;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ViewLpaSummaryImagesHandler extends AbstractHandler implements UserAware
{
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        private LpaService $lpaService,
        private InstAndPrefImagesService $instAndPrefImagesService,
        private FeatureEnabled $featureEnabled,
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user     = $this->getUser($request);
        $identity = !is_null($user) ? $user->getIdentity() : null;

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        // In order to reduce initial load on the images service we do data backed checks
        // to see if we should call it. Ideally these would live in the api layer but we
        // can't do that without a big refactor of how that works atm.
        if (!is_null($lpaData) 
            && (($lpaData->lpa->getApplicationHasGuidance() ?? false) 
            || ($lpaData->lpa->getApplicationHasRestrictions() ?? false))
        ) {
            return new JsonResponse($this->instAndPrefImagesService->getImagesById($identity, $actorLpaToken));
        }

        return new JsonResponse(
            'Not Found',
            StatusCodeInterface::STATUS_NOT_FOUND,
            ['Content-Type' => 'application/problem+json']
        );
    }
}
