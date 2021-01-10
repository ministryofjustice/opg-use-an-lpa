<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LpasActionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasActionsHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(LpaService $lpaService)
    {
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        $lpaReferenceNumber

        // Check LPA match
        $lpaMatchResponse = $this->lpaService->getByUid($requestData['reference_number']);

        //check lpa donor details matches with user provided first name, last name, dob, postcode


//        if (!is_null($lpaMatchResponse)) {
//            // Check if date LPA registered is not after Sep 2019
//            $expectedRegistrationDate = '2019-09-01';
//            if (($lpaDataMatchCheck->getRegistrationDate()) <= $expectedRegistrationDate) {
//                // UML - 1163 -> Cannot send an activation key for that LPA
//                return new HtmlResponse($this->renderer->render('cannot-send-activation-key'));
//            }
//
//            // If the correct data is entered then a letter should be requested from Sirius




        if (!isset($requestData['lpa-id'])) {
            throw new BadRequestException("'lpa-id' missing.");
        }

        if (!isset($requestData['actor-id'])) {
            throw new BadRequestException("'actor-id' missing.");
        }

        $this->lpaService->requestAccessByLetter($requestData['lpa-id'], $requestData['actor-id']);

        return new EmptyResponse();
    }
}
