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

/**
 * @codeCoverageIgnore
 */
class ViewerCodeSummaryHandler implements RequestHandlerInterface
{
    public function __construct(
        private LpaService $lpaService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException|Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (empty($params['code']) || empty($params['name'])) {
            throw new BadRequestException("'code' and 'name' are required fields.");
        }

        $data = $this->lpaService->getByViewerCode($params['code'], $params['name'], null);

        if (is_null($data)) {
            throw new NotFoundException();
        }

        return new JsonResponse($data);
    }
}
