<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
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
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        if (!isset($requestData['lpa-id'])) {
            throw new BadRequestException("'lpa-id' missing.");
        }

        if (!isset($requestData['actor-id'])) {
            throw new BadRequestException("'actor-id' missing.");
        }

        $this->lpaService->requestAccessByLetter($requestData['lpa-id'], $requestData['actor-id']);

        return new EmptyResponse();
    }
}
