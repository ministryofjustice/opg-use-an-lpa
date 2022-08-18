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
 * Class NotifyHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class NotifyHandler implements RequestHandlerInterface
{
    private LoggerInterface $logger;
    private EmailClient $emailClient;
    private NotifyService $notifyService;

    /**
     * NotifyHandler constructor.
     *
     * @param EmailClient $emailClient
     * @param LoggerInterface $logger
     * @param NotifyService $notifyService
     */
    public function __construct(
        EmailClient $emailClient,
        LoggerInterface $logger,
        NotifyService $notifyService
    )
    {
        $this->emailClient = $emailClient;
        $this->logger = $logger;
        $this->notifyService = $notifyService;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $emailTemplate = $request->getAttribute('emailTemplate');

        if (sizeof($requestData) == 0) {
            throw new BadRequestException("Required parameters not provided to send an email");
        }

        ($this->notifyService)($requestData, $emailTemplate);

        return new JsonResponse([]);
    }
}