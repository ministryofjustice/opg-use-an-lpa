<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;
use Exception;

/**
 * Class OlderLpaValidationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaValidationHandler implements RequestHandlerInterface
{
    private OlderLpaService $olderLpaService;

    public function __construct(OlderLpaService $olderLpaService)
    {
        $this->olderLpaService = $olderLpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
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

        $lpaMatchResponse = $this->olderLpaService->validateOlderLpaRequest($userId, $requestData);
        return new JsonResponse($lpaMatchResponse, 200);
    }
}
