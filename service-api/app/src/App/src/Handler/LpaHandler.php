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
        //  TODO - Possibly split this logic into a separate handler later
        $uid = $request->getAttribute('uid');
        $shareCode = $request->getAttribute('shareCode');

        $data = [];

        if (!empty($uid)) {
            $data = $this->lpaService->getById((int) $uid);
        } elseif (!empty($shareCode)) {
            $data = $this->lpaService->getByCode((int) $shareCode);
        } else {
            throw new RuntimeException('Missing LPA identifier');
        }

        return new JsonResponse($data);
    }
}
