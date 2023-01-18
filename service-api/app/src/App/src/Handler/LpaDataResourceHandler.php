<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LpasResourceHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpaDataResourceHandler implements RequestHandlerInterface
{
    public function __construct(
        private LpaService $lpaService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            default => $this->handleGet($request),
        };
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException|Exception
     */
    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info(
            '1. I am here...... .......HandleGet in LpaDataResourceHandler......{req}',
            [
                'req' => $request->getAttribute('actor-id'),
                'req1' => $request->getAttribute('user-lpa-actor-token'),
            ]
        );

        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'actor-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        $this->logger->info(
            'I am here...... .......HandleGet in LpaDataResourceHandler......2'
        );

        $result = $this->lpaService->getLpaDetailsByUserLpaActorToken(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id')
        );

        if (is_null($result)) {
            throw new NotFoundException();
        }

        return new JsonResponse($result);
    }
}
