<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class LpaSearchHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class ViewerCodeSummaryHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(LpaService $lpaService)
    {
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (!isset($params['code']) || !isset($params['name'])) {
            throw new RuntimeException("'code' and 'name' are required fields.");
        }

        $data = $this->lpaService->getByViewerCode($params['code'], $params['name'], null);

        if (is_null($data)) {
            throw new NotFoundException();
        }

        return new JsonResponse($data);
    }
}
