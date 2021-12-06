<?php

namespace AppTest\Service\Lpa;

use App\Exception\NotFoundException;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class RestrictSendingLpaForCleansingTest
 *
 * @package AppTest\Service\Lpa
 * @coversDefaultClass \App\Service\Lpa\RestrictSendingLpaForCleansing
 */
class RestrictSendingLpaForCleansingTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    public function restrictSendingLpaForCleansing(): RestrictSendingLpaForCleansing
    {
        return new RestrictSendingLpaForCleansing(
            $this->loggerProphecy->reveal(),
        );
    }

    /**
     * @test
     */
    public function throws_not_found_exception_when_lpa_status_registered_and_actorMatch_is_null(): void
    {
        $lpa = [
            'registrationDate' => '2020-05-26',
        ];

        $actorDetailsMatch = null;

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA not found');

        ($this->restrictSendingLpaForCleansing()($lpa, $actorDetailsMatch));
    }
}
