<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\PaperVerificationCodeExpiry;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Enum\VerificationCodeExpiryReason;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode as Code;
use DateTimeImmutable;
use DateTimeInterface;

class PaperVerificationCodes extends AbstractApiClient implements PaperVerificationCodesInterface
{
    use PostRequest;

    /**
     * @inheritDoc
     * @return ResponseInterface<PaperVerificationCode>
     * @throws ApiException
     * @throws NotFoundException
     */
    public function validate(Code $code): ResponseInterface
    {
        $response = $this->makePostRequest(
            'v1/paper-verification-code/validate',
            [
                'code' => (string) $code,
            ]
        );

        $codeData = json_decode((string) $response->getBody(), true);

        $lpaUid       = new LpaUid($codeData['lpa']);
        $expiresAt    = isset($codeData['expiry_date'])
            ? $this->processExpiryDate($codeData['expiry_date'])
            : null;
        $expiryReason = isset($codeData['expiry_reason'])
            ? VerificationCodeExpiryReason::tryFrom($codeData['expiry_reason'])
            : null;

        return new UpstreamResponse(
            new PaperVerificationCode($lpaUid, $expiresAt, $expiryReason),
            new DateTimeImmutable($response->getHeaderLine('Date')),
        );
    }

    /**
     * @inheritDoc
     * @param VerificationCodeExpiryReason $reason
     * @return ResponseInterface<PaperVerificationCodeExpiry>
     * @throws ApiException
     * @throws NotFoundException
     */
    public function expire(Code $code, VerificationCodeExpiryReason $reason): ResponseInterface
    {
        $response = $this->makePostRequest(
            'v1/paper-verification-code/expire',
            [
                'code'          => (string) $code,
                'expiry_reason' => $reason->value,
            ]
        );

        $codeData = json_decode((string) $response->getBody(), true);

        $expiresAt = $this->processExpiryDate($codeData['expiry_date']);

        return new UpstreamResponse(
            new PaperVerificationCodeExpiry($expiresAt),
            new DateTimeImmutable($response->getHeaderLine('Date')),
        );
    }

    private function processExpiryDate(string $date): ?DateTimeInterface
    {
        $dateInterface = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $dateInterface === false ? null : $dateInterface;
    }
}
