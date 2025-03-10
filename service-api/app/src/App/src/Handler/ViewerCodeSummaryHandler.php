<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaManagerInterface;
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
        private LpaManagerInterface $lpaManager,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws GoneException
     * @throws ApiException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (empty($params['code']) || empty($params['name'])) {
            throw new BadRequestException("'code' and 'name' are required fields.");
        }

        $data = $this->lpaManager->getByViewerCode($params['code'], $params['name'], null);

        return new JsonResponse($data);
    }
}
