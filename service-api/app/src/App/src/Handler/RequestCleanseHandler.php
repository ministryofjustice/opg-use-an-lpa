<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Log\EventCodes;
use App\Service\Lpa\LpaAlreadyAdded;
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
    private LpaAlreadyAdded $lpaAlreadyAdded;
    private OlderLpaService $olderLpaService;

    public function __construct(
        OlderLpaService $olderLpaService,
        LpaAlreadyAdded $lpaAlreadyAdded,
        LoggerInterface $logger
    ) {
        $this->olderLpaService = $olderLpaService;
        $this->logger = $logger;
        $this->lpaAlreadyAdded = $lpaAlreadyAdded;
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
        $userId = $request->getAttribute('actor-id');

        $addedData = ($this->lpaAlreadyAdded)($userId, (string)$requestData['reference_number']);

        $this->olderLpaService->requestAccessAndCleanseByLetter(
            (string)$requestData['reference_number'],
            $userId,
            $requestData['notes'],
            $requestData['actor_id'],
            $addedData['lpaActorToken'] ?? null
        );

        $this->logger->notice(
            'Successfully submitted cleanse for LPA {uId} for account {id} ',
            [
                'event_code' => EventCodes::OLDER_LPA_CLEANSE_SUCCESS,
                'id'  => $userId,
                'uId' => (string)$requestData['reference_number']
            ]
        );
        
        return new EmptyResponse();
    }
}
