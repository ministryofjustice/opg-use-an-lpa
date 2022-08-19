<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\AddLpa;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AddLpaValidationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class AddLpaValidationHandler implements RequestHandlerInterface
{
    public function __construct(
        private AddLpa $addLpa,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getHeader('user-token')[0];
        $data = $request->getParsedBody();

        if (
            empty($data['actor-code']) ||
            empty($data['uid']) ||
            empty($data['dob'])
        ) {
            throw new BadRequestException("'actor-code', 'uid' and 'dob' are required fields");
        }

        $response = $this->addLpa->validateAddLpaData($data, $userId);

        return new JsonResponse($response);
    }
}
