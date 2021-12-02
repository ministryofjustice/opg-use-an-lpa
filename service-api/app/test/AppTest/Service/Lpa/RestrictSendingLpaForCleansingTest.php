<?php

namespace AppTest\Service\Lpa;

use App\Exception\NotFoundException;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use App\Service\Features\FeatureEnabled;

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

    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);
    }

    public function restrictSendingLpaForCleansing(): RestrictSendingLpaForCleansingTest
    {
        return new RestrictSendingLpaForCleansing(
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function throws_not_found_exception_when_lpa_status_registered_and_actorMatch_is_null()
    {
        $this->featureEnabledProphecy->__invoke('allow_older_lpas')->willReturn(true);

        $lpa = [
            'registrationDate' => '2020-05-26',
        ];

        $actorDetailsMatch = null;

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA not found');

        $this->restrictSendingLpaForCleansing()($lpa, $actorDetailsMatch);

    }
}
