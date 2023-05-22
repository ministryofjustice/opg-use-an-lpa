<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\Exception\{BadRequestException, NotFoundException};
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class InstructionsAndPreferencesImagesHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class InstructionsAndPreferencesImagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private InstructionsAndPreferencesImages $iapImagesService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        // TODO uid now comes from the url
        if (empty($data['uId']) ) {
            throw new BadRequestException(" 'uid' is a required field");
        }

        $response = $this->iapImagesService->getInstructionsAndPreferencesImages(
            $data['uid'],
        );

        if (!is_string($response)) {
            throw new NotFoundException();
        }

        // TODO do we need to return a 404 if uid doesn't exist? or just no images I guess?

        return new JsonResponse(
            [
                // TODO - extract the data from DAO here
                //'user-lpa-actor-token' => $response
            ],
            // TODO this needs to be some other status, presumably just OK 200 ?
            StatusCodeInterface::STATUS_CREATED,
        );
    }
}
