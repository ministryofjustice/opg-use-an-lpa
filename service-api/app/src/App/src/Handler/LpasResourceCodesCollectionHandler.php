<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\Repository;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ViewerCodes\ViewerCodeService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class LpasResourceCodesCollectionHandler implements RequestHandlerInterface
{
    public function __construct(
        private ViewerCodeService $viewerCodeService,
        private Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        private Repository\UserLpaActorMapInterface $userLpaActorMap,
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
        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            'PUT' => $this->handlePut($request),
            default => $this->handleGet($request),
        };
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|RuntimeException|NotFoundException|Exception
     */
    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        $data = $request->getParsedBody();
        if (empty($data['organisation'])) {
            throw new RuntimeException("'organisation' is missing.");
        }

        // Sense check the passed organisation
        $organisation = trim($data['organisation']);

        if (empty($organisation) || strlen($organisation) > 200) {
            throw new RuntimeException("'organisation' is malformed.");
        }

        $result = $this->viewerCodeService->addCode(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id'),
            $organisation,
        );

        if ($result === null) {
            throw new NotFoundException();
        }

        return new JsonResponse($result);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|RuntimeException|NotFoundException|Exception
     */
    private function handlePut(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!isset($data['code'])) {
            throw new RuntimeException('share code is missing.');
        }

        $code = trim($data['code']);

        $this->viewerCodeService->cancelCode(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id'),
            $code,
        );

        return new JsonResponse([]);
    }

    /**
     * Returns a collection of codes (and statuses if codes exist)
     * for a given LPA via the user lpa token
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException|Exception
     */
    private function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        // gets access codes for a given user lpa token
        $viewerCodes = $this->viewerCodeService->getCodes(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id'),
        );

        return new JsonResponse($viewerCodes);
    }
}
