<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\AddLpa;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AddLpaVerificationHandler
{
    private AddLpa $addLpa;

    public function __construct(AddLpa $addLpa)
    {
        $this->addLpa = $addLpa;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (
            !isset($data['actor-code']) ||
            !isset($data['uid']) ||
            !isset($data['dob'])
        ) {
            throw new BadRequestException("'actor-code', 'uid' and 'dob' are required fields");
        }

        $response = $this->addLpa->validateLpaDetails($data);

        // We deliberately don't return details of why the (validated) code was not found.
        if (!is_array($response)) {
            throw new NotFoundException();
        }

        return new JsonResponse($response, 200);
    }
}
