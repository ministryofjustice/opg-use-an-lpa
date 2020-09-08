<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Service\User\UserService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class UserHandler
 * @package App\Handler
 */
class UserHandler implements RequestHandlerInterface
{
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|NotFoundException
     */
    private function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (!isset($params['email'])) {
            throw new BadRequestException('Email address must be provided');
        }

        $data = $this->userService->getByEmail($params['email']);

        return new JsonResponse($data);
    }


    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception|BadRequestException|ConflictException|NotFoundException
     */
    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        if (!isset($requestData['email']) || !isset($requestData['password'])) {
            throw new BadRequestException('Email address and password must be provided');
        }

        $data = $this->userService->add($requestData);

        return new JsonResponse($data, 201);
    }
}
