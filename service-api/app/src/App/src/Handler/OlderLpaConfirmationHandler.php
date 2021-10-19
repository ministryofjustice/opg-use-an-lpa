<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\AddOlderLpa;
use App\Service\Lpa\CheckLpaCleansed;
use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Service\Features\FeatureEnabled;

/**
 * Class OlderLpaConfirmationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaConfirmationHandler implements RequestHandlerInterface
{
    private AddOlderLpa $addOlderLpa;
    private OlderLpaService $olderLpaService;
    private FeatureEnabled $featureEnabled;
    private CheckLpaCleansed $checkLpaCleansed;


    public function __construct(
        AddOlderLpa $addOlderLpa,
        OlderLpaService $olderLpaService,
        FeatureEnabled $featureEnabled,
        CheckLpaCleansed $checkLpaCleansed
    ) {
        $this->addOlderLpa = $addOlderLpa;
        $this->olderLpaService = $olderLpaService;
        $this->featureEnabled = $featureEnabled;
        $this->checkLpaCleansed   = $checkLpaCleansed;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getHeader('user-token')[0];

        if (
            !isset($requestData['reference_number']) ||
            !isset($requestData['dob']) ||
            !isset($requestData['first_names']) ||
            !isset($requestData['last_name']) ||
            !isset($requestData['postcode'])
        ) {
            throw new BadRequestException('Required data missing to request an activation key');
        }

        $lpaMatchResponse = $this->addOlderLpa->validateRequest($userId, $requestData);

        if (($this->featureEnabled)('allow_older_lpas')) {
            ($this->checkLpaCleansed)($userId, $lpaMatchResponse);
        }

        $this->olderLpaService->requestAccessByLetter(
            (string) $requestData['reference_number'],
            $lpaMatchResponse['actor']['uId'],
            $userId,
            $lpaMatchResponse['lpaActorToken'] ?? null
        );

        return new EmptyResponse();
    }
}
