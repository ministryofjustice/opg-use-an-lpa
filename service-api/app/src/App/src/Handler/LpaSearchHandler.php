<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use RuntimeException;

/**
 * Class LpaSearchHandler
 * @package App\Handler
 */
class LpaSearchHandler implements RequestHandlerInterface
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
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['code']) || !isset($params['uid']) || !isset($params['dob'])) {
            throw new RuntimeException('Missing LPA search parameters');
        }

        $data = $this->lpaService->search($params['code'], $params['uid'], $params['dob']);

        return new JsonResponse($data);
    }
}
