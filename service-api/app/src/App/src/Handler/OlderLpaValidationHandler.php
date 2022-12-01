<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\AddOlderLpa;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class OlderLpaValidationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaValidationHandler implements RequestHandlerInterface
{
    public function __construct(
        private AddOlderLpa $addOlderLpa,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getHeader('user-token')[0];

        if (
            empty($requestData['reference_number']) ||
            empty($requestData['dob']) ||
            empty($requestData['first_names']) ||
            empty($requestData['last_name'])
        ) {
            throw new BadRequestException('Required data missing to request an activation key');
        }

        $lpaMatchResponse = $this->addOlderLpa->validateRequest($userId, $requestData);

        return new JsonResponse($lpaMatchResponse);
    }
}
