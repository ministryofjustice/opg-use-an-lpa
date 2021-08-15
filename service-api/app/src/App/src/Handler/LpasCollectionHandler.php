<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LpasCollectionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasCollectionHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var FeatureEnabled
     */
    private $featureEnabled;

    public function __construct(LpaService $lpaService, FeatureEnabled $featureEnabled)
    {
        $this->lpaService = $lpaService;
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('actor-id');

        if (($this->featureEnabled)('allow_older_lpas')) {
            $result = $this->lpaService->getAllAddedLpasForUser($user);
        } else {
            $result = $this->lpaService->getAllForUser($user);
        }

        return new JsonResponse($result);
    }
}
