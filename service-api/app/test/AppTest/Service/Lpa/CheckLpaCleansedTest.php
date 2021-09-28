<?php

namespace AppTest\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\Lpa\CheckLpaCleansed;
use PHPUnit\Framework\TestCase;
use App\Service\Lpa\IsValidLpa;
use Psr\Log\LoggerInterface;

/**
 * Class CheckLpaCleansedTest
 *
 * @package AppTest\Service\Lpa
 * @coversDefaultClass \App\Service\Lpa\CheckLpaCleansed
 */
class CheckLpaCleansedTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    public function checkLpaCleansed(): CheckLpaCleansed
    {
        return new CheckLpaCleansed(
            $this->loggerProphecy->reveal(),
        );
    }

    /** @test */
    public function older_lpa_add_confirmation_throws_an_exception_if_lpa_not_cleansed()
    {
        $actorDetailsMatch = [
            'lpaIsCleansed' => false,
        ];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA is not cleansed');

        ($this->checkLpaCleansed()($actorDetailsMatch));
    }

    /** @test */
    public function older_lpa_add_confirmation_returns_void_when_lpa_is_cleansed()
    {
        $actorDetailsMatch = [
            'lpaIsCleansed' => true,
        ];

        $result = ($this->checkLpaCleansed()($actorDetailsMatch));
        $this->assertNull($result);
    }
}
