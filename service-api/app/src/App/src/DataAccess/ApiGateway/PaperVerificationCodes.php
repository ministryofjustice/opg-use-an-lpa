<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Enum\VerificationCodeExpiryReason;
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
                'lpa' => 'M-7890-0400-4003', // no expiry as it's not been used yet
            ];
        } elseif ((string)$code === 'P-5678-5678-5678-56') {
            $codeData = [
                'lpa'     => 'M-7890-0400-4003',
                'expires' => (new DateTimeImmutable())
                    ->sub(new DateInterval('P1Y')) // code has expired
                    ->format(DateTimeInterface::ATOM),
                'expiry_reason' => 'first_time_use',
            ];
        } elseif ((string)$code === 'P-3456-3456-3456-34') {
            $codeData = [
                'lpa'       => 'M-7890-0400-4003',
                'expires'   => (new DateTimeImmutable())
                    ->add(new DateInterval('P1Y'))
                    ->format(DateTimeInterface::ATOM),
                'cancelled' => 'true', // code valid but cancelled
                'expiry_reason' => 'first_time_use',
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
        $expiryReason = isset($codeData['expiry_reason'])
            ? VerificationCodeExpiryReason::tryFrom($codeData['expiry_reason'])
            : null;

        return new UpstreamResponse(
            new PaperVerificationCode($lpaUid, $cancelled, $expiresAt, $expiryReason),
            new DateTimeImmutable('now', new DateTimeZone('UTC')), // TODO remove when mock available
            // new DateTimeImmutable($response->getHeaderLine('Date')), // TODO use when mock available
        );
    }

    /**
     * @param VerificationCodeExpiryReason $reason
     * @inheritDoc
     * @codeCoverageIgnore
     * @psalm-suppress RedundantCondition
     */
    public function expire(Code $code, VerificationCodeExpiryReason $reason): ResponseInterface
    {
        // 1. Fetch data from upstream paper verification code service (ActorCodes)
        //    a. If cancelled set expiry date a year ago
        //    b. otherwise set expiry data a year in future
        //    c. otherwise (not found) throw exception
        $dateInterval = new DateInterval('P1Y');
        $expiryDate = ($reason = VerificationCodeExpiryReason::CANCELLED ?
                 (new DateTimeImmutable())->sub($dateInterval)->format(DateTimeInterface::ATOM) :
                 (new DateTimeImmutable())->add($dateInterval)->format(DateTimeInterface::ATOM));
        if ((string)$code === 'P-1234-1234-1234-12') {
            $codeData = [
                'lpa'     => 'M-7890-0400-4003',
                'expires' => $expiryDate,
                'expiry_reason' => $reason,
            ];
        } else {
            throw new NotFoundException();
        }
        // code above constitutes a mocked code service call
        // TODO swap when mock available
//        $response = $this->makePostRequest(
//            'v1/paper-verification-code/expire',
//            [
//                'code' => $code,
//                'reason' => $reason->value,
//            ]
//        );
//
//        $codeData = json_decode((string) $response->getBody(), true);

        $lpaUid    = new LpaUid($codeData['lpa']);
        $expiresAt = isset($codeData['expires'])
            ? new DateTimeImmutable($codeData['expires'])
            : null;
        $expiryReason = isset($codeData['expiry_reason'])
            ? VerificationCodeExpiryReason::tryFrom($codeData['expiry_reason'])
            : null;

        return new UpstreamResponse(
            new PaperVerificationCode($lpaUid, false, $expiresAt, $expiryReason),
            new DateTimeImmutable('now', new DateTimeZone('UTC')), // TODO remove when mock available
        // new DateTimeImmutable($response->getHeaderLine('Date')), // TODO use when mock available
        );
    }

}
