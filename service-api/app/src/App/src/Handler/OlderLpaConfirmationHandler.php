<?php


namespace App\Handler;


use App\Exception\BadRequestException;
use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class OlderLpaConfirmationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaConfirmationHandler implements RequestHandlerInterface
{
    private OlderLpaService $olderLpaService;

    public function __construct(OlderLpaService $olderLpaService)
    {
        $this->olderLpaService = $olderLpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        //When a first time or forced request for an activation key
        $this->olderLpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);
        return new EmptyResponse();
    }

}