<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Handler\Trait\RequestAsObjectTrait;
use App\Request\ViewerCodeSummary;
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
    /** @use RequestAsObjectTrait<ViewerCodeSummary> */
    use RequestAsObjectTrait;

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
        $params = $this->requestAsObject($request, ViewerCodeSummary::class);

        $data = $this->lpaManager->getByViewerCode($params->code, $params->name);

        return new JsonResponse($data);
    }
}
