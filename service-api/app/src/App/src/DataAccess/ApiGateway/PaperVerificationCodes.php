<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Enum\VerificationCodeExpiryReason;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode as Code;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface as PSRResponseInterface;

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

        return $this->processResponse($response);
    }

    /**
     * @inheritDoc
     * @param VerificationCodeExpiryReason $reason
     * @return ResponseInterface<PaperVerificationCode>
     * @throws ApiException
     * @throws NotFoundException
     */
    public function expire(Code $code, VerificationCodeExpiryReason $reason): ResponseInterface
    {
        $response = $this->makePostRequest(
            'v1/paper-verification-code/expire',
            [
                'code'   => (string) $code,
                'reason' => $reason->value,
            ]
        );

        return $this->processResponse($response);
    }

    private function processResponse(PSRResponseInterface $response): ResponseInterface
    {
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

    private function processExpiryDate(string $date): ?DateTimeInterface
    {
        $dateInterface = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $dateInterface === false ? null : $dateInterface;
    }
}
