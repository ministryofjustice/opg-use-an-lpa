<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class ActorCodePreviewHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class ActorCodeSummaryHandler implements RequestHandlerInterface
{
    /**
     * @var ActorCodeService
     */
    private $actorCodeService;

    public function __construct(ActorCodeService $actorCodeService)
    {
        $this->actorCodeService = $actorCodeService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!isset($data['actor-code']) || !isset($data['uid']) || !isset($data['dob'])) {
            throw new BadRequestException("'actor-code', 'uid' and 'dob' are required fields");
        }

        $response = $this->actorCodeService->validateDetails($data['actor-code'], $data['uid'], $data['dob']);

        // We deliberately don't return details of why the (validated) code was not found.
        if (!is_array($response)) {
            throw new NotFoundException();
        }

        return new JsonResponse($response, 200);
    }
}
