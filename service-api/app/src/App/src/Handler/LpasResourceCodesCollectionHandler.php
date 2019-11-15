<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ViewerCodes\ViewerCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
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
        Repository\UserLpaActorMapInterface $userLpaActorMap)
    {
        $this->viewerCodeService = $viewerCodeService;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->userLpaActorMap = $userLpaActorMap;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        if ($request->getMethod() === 'POST') {

            $data = $request->getParsedBody();

            if (!isset($data['organisation'])) {
                throw new RuntimeException("'organisation' is missing.");
            }

            //---

            // Sense check the passed organisation

            $organisation = trim($data['organisation']);

            if (empty($organisation) || strlen($organisation) > 200) {
                throw new RuntimeException("'organisation' is malformed.");
            }

            //---

            $result = $this->viewerCodeService->addCode(
                $request->getAttribute('user-lpa-actor-token'),
                $request->getAttribute('actor-id'),
                $organisation
            );

            if (is_null($result)) {
                throw new NotFoundException();
            }

            return new JsonResponse($result);

        } else {

            $viewerCodes = $this->viewerCodeService->getCodes(
                $request->getAttribute('user-lpa-actor-token'),
                $request->getAttribute('actor-id')
            );

            if (!empty($viewerCodes)) {
                $viewerCodesAndStatuses = $this->viewerCodeActivityRepository->getStatusesForViewerCodes($viewerCodes);

                $actorId = $this->userLpaActorMap->getUsersLpas($request->getAttribute('actor-id'));

                //adds an actorId for each code in the array
                foreach ($viewerCodesAndStatuses as $key => $code){
                    $viewerCodesAndStatuses[$key]['ActorId'] = $actorId[0]['ActorId'];
                }

                return new JsonResponse($viewerCodesAndStatuses);
            }

            return new JsonResponse($viewerCodes);
        }

    }
}
