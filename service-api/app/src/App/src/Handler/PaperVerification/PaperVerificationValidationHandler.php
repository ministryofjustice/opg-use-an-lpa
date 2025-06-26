<?php

declare(strict_types=1);

namespace App\Handler\PaperVerification;

use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Handler\Trait\RequestAsObjectTrait;
use App\Request\PaperVerificationCodeValidate;
use App\Request\ViewerCodeFull;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\PaperVerificationCodes\PaperVerificationCodeService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class PaperVerificationValidationHandler implements RequestHandlerInterface
{
    /** @use RequestAsObjectTrait<PaperVerificationCodeValidate> */
    use RequestAsObjectTrait;

    public function __construct(
        private LpaManagerInterface $lpaManager,
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
        $params = $this->requestAsObject($request, PaperVerificationCodeValidate::class);

        $data = $this->codeService->validate($params);

        return new JsonResponse($data);
    }
}
