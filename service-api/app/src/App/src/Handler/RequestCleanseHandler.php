<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Log\EventCodes;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use Exception;
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
    public function __construct(
        private LpaService $lpaService,
        private OlderLpaService $olderLpaService,
        private LpaAlreadyAdded $lpaAlreadyAdded,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getAttribute('actor-id');

        if (
            empty($requestData['reference_number']) ||
            empty($requestData['notes'])
        ) {
            throw new BadRequestException('Required data missing to request an lpa cleanse');
        }

        $lpa        = $this->lpaService->getByUid((string) $requestData['reference_number']);
        $lpaData    = $lpa->getData();

        $addedData  = ($this->lpaAlreadyAdded)($userId, (string) $requestData['reference_number']);
        $actorId    = $requestData['actor_id'] ?? null;

        $this->olderLpaService->requestAccessAndCleanseByLetter(
            (string) $requestData['reference_number'],
            $userId,
            $requestData['notes'],
            $actorId ? (int) $actorId : null,
            $addedData['lpaActorToken'] ?? null,
        );

        $this->logger->notice(
            'Successfully submitted cleanse for partially matched LPA {uId} for account {id} ',
            [
                'event_code' => ($lpaData['caseSubtype'] === 'hw') ?
                    EventCodes::PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW :
                    EventCodes::PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PF,
                'id'         => $userId,
                'uId'        => (string) $requestData['reference_number'],
                'lpaType'    => $lpaData['caseSubtype'],
            ],
        );

        $this->logger->notice(
            'Successfully submitted cleanse for LPA {uId} for account {id} ',
            [
                'event_code' => EventCodes::OLDER_LPA_CLEANSE_SUCCESS,
                'id'         => $userId,
                'uId'        => (string) $requestData['reference_number'],
            ],
        );

        return new EmptyResponse();
    }
}
