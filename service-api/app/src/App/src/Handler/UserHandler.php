<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Service\User\UserService;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class UserHandler implements RequestHandlerInterface
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException|Exception
     */
    private function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (empty($params['email'])) {
            throw new BadRequestException('Email address must be provided');
        }

        $data = $this->userService->getByEmail($params['email']);

        return new JsonResponse($data);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|ConflictException|Exception
     */
    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        if (empty($requestData['email']) || empty($requestData['password'])) {
            throw new BadRequestException('Email address and password must be provided');
        }

        $requestData['password'] = new HiddenString($requestData['password']);
        $data                    = $this->userService->add($requestData);

        return new JsonResponse($data, StatusCodeInterface::STATUS_CREATED);
    }
}
