<?php

declare(strict_types=1);

namespace App\Handler\PaperVerificationCodes;

use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Handler\Trait\RequestAsObjectTrait;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeView;
use App\Service\PaperVerificationCodes\PaperVerificationCodeService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class ViewHandler implements RequestHandlerInterface
{
    /** @use RequestAsObjectTrait<PaperVerificationCodeUsable> */
    use RequestAsObjectTrait;

    public function __construct(
        private PaperVerificationCodeService $codeService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws GoneException
     * @throws ApiException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $this->requestAsObject($request, PaperVerificationCodeView::class);

        $response = $this->codeService->view($params);

        return new JsonResponse($response);
    }
}
