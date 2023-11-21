<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use App\Service\Notify\NotifyService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * @codeCoverageIgnore
 */
class NotifyHandler implements RequestHandlerInterface
{
    public function __construct(
        private EmailClient $emailClient,
        private LoggerInterface $logger,
        private NotifyService $notifyService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData   = $request->getParsedBody();
        $emailTemplate = $request->getAttribute('emailTemplate');

        if (count($requestData) === 0) {
            throw new BadRequestException('Required parameters not provided to send an email');
        }

        ($this->notifyService)($emailTemplate, $requestData);

        return new JsonResponse([]);
    }
}
