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

    public function __construct(ViewerCodeService $viewerCodeService)
    {
        $this->viewerCodeService = $viewerCodeService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if (is_null($request->getAttribute('user-id'))) {
            throw new BadRequestException("'user-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        if ($request->getMethod() == 'POST') {

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
                $request->getAttribute('user-id'),
                $organisation
            );

            if (is_null($result)){
                throw new NotFoundException();
            }

            return new JsonResponse($result);
        } else {
            die('GET Not Implemented');
        }

    }
}
