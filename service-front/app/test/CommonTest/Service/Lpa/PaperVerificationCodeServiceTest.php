<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use AppTest\LpaUtilities;
use ArrayObject;
use CommonTest\Helper\EntityTestHelper;
use Common\Entity\CombinedLpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\PaperVerificationCodeService;
use Common\Service\Lpa\PaperVerificationCodeStatus;
use Common\Service\Lpa\ParseLpaData;
use Common\Service\Lpa\Response\PaperVerificationCode;
use Common\Service\Lpa\Response\Parse\ParsePaperVerificationCode;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class PaperVerificationCodeServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|Client $apiClientProphecy;
    private ObjectProphecy|LoggerInterface $loggerProphecy;
    private ObjectProphecy|ParseLpaData $parsePaperVerificationCode;
    private ObjectProphecy|PaperVerificationService $paperVerificationService;

    public function setUp(): void
    {
        $this->apiClientProphecy          = $this->prophesize(Client::class);
        $this->parsePaperVerificationCode = $this->prophesize(ParsePaperVerificationCode::class);
        $this->parseLpaData               = $this->prophesize(ParseLpaData::class);
        $this->loggerProphecy             = $this->prophesize(LoggerInterface::class);

        $this->paperVerificationCodeService = new PaperVerificationCodeService(
            $this->apiClientProphecy->reveal(),
            $this->parsePaperVerificationCode->reveal(),
            $this->parseLpaData->reveal(),
            $this->loggerProphecy->reveal()
        );
    }

    #[Test]
    public function usable(): void
    {
        $responseData = ['status' => 'registered'];
        $parsedData   = new PaperVerificationCode('donor', 'hw', 'registered', 'somewhere');

        $this->apiClientProphecy->httpPost('/v1/paper-verification/usable', [
            'code' => 'P-1234-1234-1234-12',
            'name' => 'Sanderson',
        ])->willReturn($responseData);

        $this->parsePaperVerificationCode->__invoke($responseData)->willReturn($parsedData);

        $result = $this->paperVerificationCodeService->usable('P-1234-1234-1234-12', 'Sanderson');
        $this->assertEquals(PaperVerificationCodeStatus::OK, $result->status);
        $this->assertEquals($parsedData, $result->data);
    }

    #[Test]
    public function usable_cancelled(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'cancelled']));

        $result = $this->paperVerificationCodeService->usable('P-1234-1234-1234-12', 'Sanderson');
        $this->assertEquals(PaperVerificationCodeStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function usable_expired(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'expired']));

        $result = $this->paperVerificationCodeService->usable('P-1234-1234-1234-12', 'Sanderson');
        $this->assertEquals(PaperVerificationCodeStatus::EXPIRED, $result->status);
    }

    #[Test]
    public function usable_not_found(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('', StatusCodeInterface::STATUS_NOT_FOUND));

        $result = $this->paperVerificationCodeService->usable('P-1234-1234-1234-12', 'Sanderson');
        $this->assertEquals(PaperVerificationCodeStatus::NOT_FOUND, $result->status);
    }

    #[Test]
    public function usable_other_problem(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectException(ApiException::class);
        $this->paperVerificationCodeService->usable('P-1234-1234-1234-12', 'Sanderson');
    }

    #[Test]
    public function validate(): void
    {
        $responseData = ['status' => 'registered'];
        $parsedData   = new PaperVerificationCode('donor', 'hw', 'registered', 'somewhere');

        $this->apiClientProphecy->httpPost('/v1/paper-verification/validate', [
            'code'          => 'P-1234-1234-1234-12',
            'name'          => 'Sanderson',
            'lpaUid'        => 'M-0123-0123-0123',
            'sentToDonor'   => true,
            'attorneyName'  => 'John Smithson',
            'dateOfBirth'   => '2003-01-02',
            'noOfAttorneys' => 4,
        ])->willReturn($responseData);

        $this->parsePaperVerificationCode->__invoke($responseData)->willReturn($parsedData);

        $result = $this->paperVerificationCodeService->validate(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
        );

        $this->assertEquals(PaperVerificationCodeStatus::OK, $result->status);
        $this->assertEquals($parsedData, $result->data);
    }

    #[Test]
    public function validate_cancelled(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'cancelled']));

        $result = $this->paperVerificationCodeService->validate(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
        );
        $this->assertEquals(PaperVerificationCodeStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function validate_expired(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'expired']));

        $result = $this->paperVerificationCodeService->validate(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
        );
        $this->assertEquals(PaperVerificationCodeStatus::EXPIRED, $result->status);
    }

    #[Test]
    public function validate_not_found(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('', StatusCodeInterface::STATUS_NOT_FOUND));

        $result = $this->paperVerificationCodeService->validate(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
        );
        $this->assertEquals(PaperVerificationCodeStatus::NOT_FOUND, $result->status);
    }

    #[Test]
    public function validate_other_problem(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectException(ApiException::class);
        $this->paperVerificationCodeService->validate(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
        );
    }

    #[Test]
    public function view(): void
    {
        $lpa          = EntityTestHelper::makeCombinedLpa();
        $responseData = ['lpa' => ['status' => 'registered']];
        $parsedData   = new ArrayObject(['lpa' => $lpa]);

        $this->apiClientProphecy->httpPost('/v1/paper-verification/view', [
            'code'          => 'P-1234-1234-1234-12',
            'name'          => 'Sanderson',
            'lpaUid'        => 'M-0123-0123-0123',
            'sentToDonor'   => true,
            'attorneyName'  => 'John Smithson',
            'dateOfBirth'   => '2003-01-02',
            'noOfAttorneys' => 4,
            'organisation'  => 'My Org',
        ])->willReturn($responseData);

        $this->parseLpaData->__invoke($responseData)->willReturn($parsedData);

        $result = $this->paperVerificationCodeService->view(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
            organisation: 'My Org',
        );

        $this->assertEquals(PaperVerificationCodeStatus::OK, $result->status);
        $this->assertEquals($lpa, $result->data);
    }

    #[Test]
    public function view_cancelled(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'cancelled']));

        $result = $this->paperVerificationCodeService->view(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
            organisation: 'My Org',
        );
        $this->assertEquals(PaperVerificationCodeStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function view_expired(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_GONE, null, ['reason' => 'expired']));

        $result = $this->paperVerificationCodeService->view(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
            organisation: 'My Org',
        );
        $this->assertEquals(PaperVerificationCodeStatus::EXPIRED, $result->status);
    }

    #[Test]
    public function view_not_found(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('boo', StatusCodeInterface::STATUS_NOT_FOUND));

        $result = $this->paperVerificationCodeService->view(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
            organisation: 'My Org',
        );
        $this->assertEquals(PaperVerificationCodeStatus::NOT_FOUND, $result->status);
    }

    #[Test]
    public function view_other_problem(): void
    {
        $this->apiClientProphecy->httpPost(Argument::any(), Argument::any())
            ->willThrow(new ApiException('', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectException(ApiException::class);
        $this->paperVerificationCodeService->view(
            code: 'P-1234-1234-1234-12',
            donorSurname: 'Sanderson',
            lpaReference: 'M-0123-0123-0123',
            sentToDonor: true,
            attorneyName: 'John Smithson',
            dateOfBirth: new DateTime('2003-01-02'),
            noOfAttorneys: 4,
            organisation: 'My Org',
        );
    }
}
