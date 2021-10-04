<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RequestCleanseHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class RequestCleanseHandler implements RequestHandlerInterface
{
    private LoggerInterface $logger;
    private OlderLpaService $olderLpaService;

    public function __construct(
        OlderLpaService $olderLpaService,
        LoggerInterface $logger
    ) {
        $this->olderLpaService = $olderLpaService;
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getHeader('user-token')[0];

        $this->olderLpaService->requestAccessAndCleanseByLetter(
            (string)$requestData['reference_number'],
            $userId,
            $requestData['notes'],
        );

        return new EmptyResponse();
    }
}
