<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\AddOlderLpa;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * Class OlderLpaValidationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaValidationHandler implements RequestHandlerInterface
{
    private AddOlderLpa $addOlderLpa;

    public function __construct(AddOlderLpa $addOlderLpa)
    {
        $this->addOlderLpa = $addOlderLpa;
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

        $lpaMatchResponse = $this->addOlderLpa->validateRequest($userId, $requestData);
        return new JsonResponse($lpaMatchResponse, 200);
    }
}
