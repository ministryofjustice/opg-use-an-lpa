<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\Repository;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class LpasResourceImagesCollectionHandler implements RequestHandlerInterface
{
    public function __construct(
        private Repository\InstructionsAndPreferencesImagesInterface $iapImages,
        private Repository\UserLpaActorMapInterface $userLpaActorMapRepository,
    ) {
    }

    /**
     * Returns an instructions and preferences images collection for a given LPA via the user lpa token.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException|Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        $map = $this->userLpaActorMapRepository->get($request->getAttribute('user-lpa-actor-token'));

        // Ensure the passed userId matches the passed token
        if (
            $map === null
            || $request->getAttribute('actor-id') !== $map['UserId']
        ) {
            throw new NotFoundException();
        }

        $images = $this->iapImages->getInstructionsAndPreferencesImages((int) $map['SiriusUid']);

        return new JsonResponse($images);
    }
}
