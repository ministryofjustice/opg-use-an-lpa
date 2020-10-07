<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ViewerCodes\ViewerCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use App\DataAccess\Repository;
use RuntimeException;

/**
 * Class LpasResourceCodesCollectionHandler
 * @package App\Handler
 */
class LpasResourceCodesCollectionHandler implements RequestHandlerInterface
{
    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;

    /**
     * @var Repository\ViewerCodeActivityInterface
     */
    private $viewerCodeActivityRepository;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMap;

    public function __construct(
        ViewerCodeService $viewerCodeService,
        Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMap
    ) {
        $this->viewerCodeService = $viewerCodeService;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->userLpaActorMap = $userLpaActorMap;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            case 'PUT':
                return $this->handlePut($request);
            default:
                return $this->handleGet($request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception|BadRequestException|ConflictException|NotFoundException
     */
    private function handlePost(ServerRequestInterface $request) : ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        $data = $request->getParsedBody();
        if (!isset($data['organisation'])) {
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
            $organisation
        );

        if (is_null($result)) {
            throw new NotFoundException();
        }

        return new JsonResponse($result);

    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException
     */
    private function handlePut(ServerRequestInterface $request) : ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!isset($data['code'])) {
            throw new RuntimeException("share code is missing.");
        }

        $code = trim($data['code']);

        $this->viewerCodeService->cancelCode(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id'),
            $code
        );

        return new JsonResponse([]);
    }

    /**
     * Returns a collection of codes (and statuses if codes exist)
     * for a given LPA via the user lpa token
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException
     */
    private function handleGet(ServerRequestInterface $request) : ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        //gets access codes for a given user lpa token
        $viewerCodes = $this->viewerCodeService->getCodes(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id')
        );

        if (!empty($viewerCodes)) {
            $viewerCodesAndStatuses = $this->viewerCodeActivityRepository->getStatusesForViewerCodes($viewerCodes);

            /// Get the actor id for the respective sharecode by UserLpaActor
            foreach ($viewerCodesAndStatuses as $key => $viewerCode){
                if(!empty($viewerCode['UserLpaActor'])) {
                     $codeOwner = $this->userLpaActorMap->get($viewerCode['UserLpaActor']);
                     $viewerCodesAndStatuses[$key]['ActorId'] = $codeOwner['ActorId'];
                }
            }
            return new JsonResponse($viewerCodesAndStatuses);
        }
        return new JsonResponse($viewerCodes);
    }
}
