<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\Lpa\ValidateAccessForAllLpaRequirements;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(ValidateAccessForAllLpaRequirements::class)]
class ValidateAccessForAllLpaRequirementsTest extends TestCase
{
    private LoggerInterface|MockObject $mockLogger;

    public function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function validateLpaRequirements(): ValidateAccessForAllLpaRequirements
    {
        return new ValidateAccessForAllLpaRequirements(
            $this->mockLogger,
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function throws_bad_request_exception_when_lpa_status_is_not_registered(): void
    {
        $lpa = [
            'uId'    => '123456789012',
            'status' => 'Pending',
        ];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function when_allow_older_lpa_flag_on_throws_exception_when_status_is_not_registered(): void
    {
        $lpa = [
            'uId'              => '123456789012',
            'status'           => 'Pending',
            'registrationDate' => '2019-08-31',
        ];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA status invalid');
        $this->validateLpaRequirements()($lpa);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function when_allow_older_lpa_flag_on_throws_no_exception_when_status_is_registered(): void
    {
        $lpa = [
            'uId'              => '123456789012',
            'status'           => 'Registered',
            'registrationDate' => '2019-08-31',
        ];

        $response = $this->validateLpaRequirements()($lpa);
        $this->assertNull($response);
    }
}
