<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LpasActionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasActionsHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(LpaService $lpaService)
    {
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $actorId = $request->getAttribute('actor-id');

        if (
            !isset($actorId) ||
            !isset($requestData['reference_number']) ||
            !isset($requestData['dob']) ||
            !isset($requestData['first_names']) ||
            !isset($requestData['last_name']) ||
            !isset($requestData['postcode'])
        ) {
            throw new BadRequestException("Required data missing!");
        }
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->lpaService->checkLPAMatch($requestData, $actorId);

        if (is_null($lpaMatchResponse)) {
            throw new NotFoundException('LPA not found');
        }

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException("'lpa-id' missing.");
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException("'actor-id' missing.");
        }

        $this->lpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);

        return new EmptyResponse();
    }
}
