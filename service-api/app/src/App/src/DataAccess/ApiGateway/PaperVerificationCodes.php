<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Exception\NotFoundException;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode as Code;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

class PaperVerificationCodes extends AbstractApiClient implements PaperVerificationCodesInterface
{
    use PostRequest;

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function validate(Code $code): ResponseInterface
    {
        // 1. Fetch data from upstream paper verification code service (ActorCodes)
        //    a. If invalid throw PaperVerificationCode\NotFoundException
        //    b. If cancelled throw PaperVerificationCode\CancelledException is GoneException
        //    c. If expired throw PaperVerificationCode\ExpiredException is GoneException
        if ((string)$code === 'P-1234-1234-1234-12') {
            $codeData = [
                'lpa' => 'M-789Q-P4DF-4UX3', // no expiry as it's not been used yet
            ];
        } elseif ((string)$code === 'P-5678-5678-5678-56') {
            $codeData = [
                'lpa'     => 'M-789Q-P4DF-4UX3',
                'expires' => (new DateTimeImmutable())
                    ->sub(new DateInterval('P1Y')) // code has expired
                    ->format(DateTimeInterface::ATOM),
            ];
        } elseif ((string)$code === 'P-3456-3456-3456-34') {
            $codeData = [
                'lpa'       => 'M-789Q-P4DF-4UX3',
                'expires'   => (new DateTimeImmutable())
                    ->add(new DateInterval('P1Y'))
                    ->format(DateTimeInterface::ATOM),
                'cancelled' => 'true', // code valid but cancelled
            ];
        } else {
            throw new NotFoundException();
        }
        // code above constitutes a mocked code service call

        // TODO swap when mock available
//        $response = $this->makePostRequest(
//            'v1/paper-verification-code/validate',
//            [
//                'code' => $code,
//            ]
//        );
//
//        $codeData = json_decode((string) $response->getBody(), true);

        $lpaUid    = new LpaUid($codeData['lpa']);
        $cancelled = isset($codeData['cancelled'])
            ? filter_var($codeData['cancelled'], FILTER_VALIDATE_BOOLEAN)
            : false;
        $expiresAt = isset($codeData['expires'])
            ? new DateTimeImmutable($codeData['expires'])
            : null;

        return new UpstreamResponse(
            new PaperVerificationCode($lpaUid, $cancelled, $expiresAt),
            new DateTimeImmutable('now', new DateTimeZone('UTC')), // TODO remove when mock available
            // new DateTimeImmutable($response->getHeaderLine('Date')), // TODO use when mock available
        );
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function startExpiry(Code $code): ResponseInterface
    {
        throw new RuntimeException('Not implemented');
    }
}
