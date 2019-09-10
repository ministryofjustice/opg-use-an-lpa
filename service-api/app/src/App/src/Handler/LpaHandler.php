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
 * Class LpaHandler
 * @package App\Handler
 */
class LpaHandler implements RequestHandlerInterface
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
        $shareCode = $request->getAttribute('shareCode');

        if (empty($shareCode)) {
            throw new RuntimeException('Missing LPA share code');
        }

        $data = $this->lpaService->getByCode($shareCode);

        return new JsonResponse($data);
    }
}
