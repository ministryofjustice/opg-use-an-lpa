<?php

namespace AppTest\Handler;

use App\Exception\ApiException;
use App\Handler\LpasActionsHandler;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\OlderLpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

class LpasActionsHandlerTest extends TestCase
{
    public function test_when_sirius_is_down_remove_stored_LPA_request()
    {
        $olderLpaServiceProphecy = $this->prophesize(OlderLpaService::class);
        $featureEnabledProphecy =  $this->getMockBuilder(FeatureEnabled::class)->setConstructorArgs([['save_older_lpa_requests']])->setMethods(['__invoke'])->getMock();


        $requestData = [
            'reference_number' => 'number',
            'dob' => ['d','o','b'],
            'first_names' => 'first name',
            'last_name' => 'last name',
            'postcode' => 'postcode'
        ];

        $matchedLPAData = [
            'lpa-id' => 'number',
            'actor-id' => '123',
        ];

        $handler = new LpasActionsHandler($olderLpaServiceProphecy->reveal(), $featureEnabledProphecy);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn($requestData);
        $requestProphecy->getAttribute('actor-id')->willReturn('123');

        $olderLpaServiceProphecy->checkLPAMatchAndGetActorDetails('123', $requestData)->willReturn($matchedLPAData);

        $featureEnabledProphecy->method('__invoke')->willReturn(true);

        $olderLpaServiceProphecy->storeLPARequest('number', '123', '123')->shouldBeCalled()->willReturn('recordId');

        $olderLpaServiceProphecy->hasActivationCode('number', '123')->willReturn(null);

        $olderLpaServiceProphecy->requestAccessByLetter('number', '123')->willThrow(new ApiException('Uh-oh'));

        $exceptionRaised = false;
        try {
            $handler->handle($requestProphecy->reveal());
        } catch (ApiException $apiException) {
            $exceptionRaised = true;
            $olderLpaServiceProphecy->removeLpaRequest('recordId')->shouldBeCalled();
        }

        if (!$exceptionRaised) {
            self::fail('Exception should be raised when there is a bad response from sirius');
        }
    }

    public function test_no_records_stored_or_removed_when_feature_flag_is_false()
    {
        $olderLpaServiceProphecy = $this->prophesize(OlderLpaService::class);
        $featureEnabledProphecy =  $this->getMockBuilder(FeatureEnabled::class)->setConstructorArgs([['save_older_lpa_requests']])->setMethods(['__invoke'])->getMock();


        $requestData = [
            'reference_number' => 'number',
            'dob' => ['d','o','b'],
            'first_names' => 'first name',
            'last_name' => 'last name',
            'postcode' => 'postcode'
        ];

        $matchedLPAData = [
            'lpa-id' => 'number',
            'actor-id' => '123',
        ];

        $handler = new LpasActionsHandler($olderLpaServiceProphecy->reveal(), $featureEnabledProphecy);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn($requestData);
        $requestProphecy->getAttribute('actor-id')->willReturn('123');

        $olderLpaServiceProphecy->checkLPAMatchAndGetActorDetails('123', $requestData)->willReturn($matchedLPAData);

        $featureEnabledProphecy->method('__invoke')->willReturn(false);

        $olderLpaServiceProphecy->storeLPARequest('number', '123', '123')->shouldNotBeCalled();

        $olderLpaServiceProphecy->hasActivationCode('number', '123')->willReturn(null);

        $olderLpaServiceProphecy->requestAccessByLetter('number', '123')->willThrow(new ApiException('Uh-oh'));

        $exceptionRaised = false;
        try {
            $handler->handle($requestProphecy->reveal());
        } catch (ApiException $apiException) {
            $exceptionRaised = true;
            $olderLpaServiceProphecy->removeLpaRequest('recordId')->shouldNotBeCalled();
        }

        if (!$exceptionRaised) {
            self::fail('Exception should be raised when there is a bad response from sirius');
        }
    }
}
