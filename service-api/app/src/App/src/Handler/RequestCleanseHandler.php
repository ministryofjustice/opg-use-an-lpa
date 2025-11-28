<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Log\EventCodes;
use App\Service\Lpa\AccessForAll\AccessForAllLpaService;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaManagerInterface;
use App\Value\LpaUid;
use Exception;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class RequestCleanseHandler implements RequestHandlerInterface
{
    public function __construct(
        private LpaManagerInterface $lpaManager,
        private AccessForAllLpaService $accessForAllLpaService,
        private LpaAlreadyAdded $lpaAlreadyAdded,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId      = $request->getAttribute('actor-id');

        if (
            empty($requestData['reference_number']) ||
            empty($requestData['notes'])
        ) {
            throw new BadRequestException('Required data missing to request an lpa cleanse');
        }

        $lpa     = $this->lpaManager->getByUid(new LpaUid($requestData['reference_number']));
        $lpaData = $lpa->getData();

        $addedData = ($this->lpaAlreadyAdded)($userId, (string) $requestData['reference_number']);

        $this->accessForAllLpaService->requestAccessAndCleanseByLetter(
            new LpaUid($requestData['reference_number']),
            $userId,
            $requestData['notes'],
            $requestData['actor_id'] ?? null,
            $addedData['lpaActorToken'] ?? null,
        );

        $this->logger->notice(
            'Successfully submitted cleanse for partially matched LPA {uId} for account {id} ',
            [
                'event_code' => $lpaData->getCaseSubType() === 'hw' ?
                    EventCodes::PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW :
                    EventCodes::PARTIAL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA,
                'id'         => $userId,
                'uId'        => (string) $requestData['reference_number'],
                'lpaType'    => $lpaData->getCaseSubType(),
            ],
        );

        return new EmptyResponse();
    }
}
