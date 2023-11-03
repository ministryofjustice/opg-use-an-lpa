<?php

declare(strict_types=1);

namespace App\Handler;

use DateTime;
use DateTimeInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class OneLoginAuthenticationCallbackHandler implements RequestHandlerInterface
{
    public function __construct()
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getQueryParams();

        $user = [
            'Id'        => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
            'Email'     => 'opg-use-an-lpa+test-user@digital.justice.gov.uk',
            'LastLogin' => (new DateTime('-1 day'))->format(DateTimeInterface::ATOM),
        ];

        return new JsonResponse($user);
    }
}
