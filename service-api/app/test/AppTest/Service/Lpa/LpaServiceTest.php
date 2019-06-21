<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\ViewerCodeActivityInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Service\ApiClient\ClientInterface;
use DateTime;
use PHPUnit\Framework\TestCase;

class LpaServiceTest extends TestCase
{
    public function testCanCreateAnInstance()
    {
        $viewerRepository = $this->prophesize(ViewerCodesInterface::class);

        $activityRepository = $this->prophesize(ViewerCodeActivityInterface::class);

        $apiClient = $this->prophesize(ClientInterface::class);

        $instance = new LpaService(
            $viewerRepository->reveal(),
            $activityRepository->reveal(),
            $apiClient->reveal()
        );

        $this->assertInstanceOf(LpaService::class, $instance);
    }

    public function testGetByValidId()
    {
        $viewerRepository = $this->prophesize(ViewerCodesInterface::class);

        $activityRepository = $this->prophesize(ViewerCodeActivityInterface::class);

        $apiClient = $this->prophesize(ClientInterface::class);
        $apiClient->httpGet('/lpas/12345678901')
            ->willReturn([
                'id' => '12345678901'
            ]);

        $instance = new LpaService(
            $viewerRepository->reveal(),
            $activityRepository->reveal(),
            $apiClient->reveal()
        );

        $lpa = $instance->getById('12345678901');

        $this->assertIsArray($lpa);
        $this->assertArrayHasKey('id', $lpa);
        $this->assertEquals($lpa['id'], '12345678901');
    }

    public function testGetByInvalidId()
    {
        $viewerRepository = $this->prophesize(ViewerCodesInterface::class);

        $activityRepository = $this->prophesize(ViewerCodeActivityInterface::class);

        $apiClient = $this->prophesize(ClientInterface::class);
        $apiClient->httpGet('/lpas/bad-id')
            ->willThrow(new NotFoundException());

        $instance = new LpaService(
            $viewerRepository->reveal(),
            $activityRepository->reveal(),
            $apiClient->reveal()
        );

        $this->expectException(NotFoundException::class);

        $lpa = $instance->getById('bad-id');
    }

    public function testGetByValidShareCode()
    {
        $viewerRepository = $this->prophesize(ViewerCodesInterface::class);
        $viewerRepository->get('share-code')
            ->willReturn([
                'Expires' => new DateTime('tomorrow'),
                'ViewerCode' => 'viewer-code',
                'SiriusId' => '12345678901'
            ]);

        $activityRepository = $this->prophesize(ViewerCodeActivityInterface::class);
        $activityRepository->recordSuccessfulLookupActivity('viewer-code')->shouldBeCalled();

        $apiClient = $this->prophesize(ClientInterface::class);
        $apiClient->httpGet('/lpas/12345678901')
            ->willReturn([
                'id' => '12345678901'
            ]);

        $instance = new LpaService(
            $viewerRepository->reveal(),
            $activityRepository->reveal(),
            $apiClient->reveal()
        );

        $lpa = $instance->getByCode('share-code');

        $this->assertIsArray($lpa);
        $this->assertArrayHasKey('id', $lpa);
        $this->assertEquals($lpa['id'], '12345678901');
    }

    public function testGetByExpiredShareCode()
    {
        $viewerRepository = $this->prophesize(ViewerCodesInterface::class);
        $viewerRepository->get('share-code')
            ->willReturn([
                'Expires' => new DateTime('yesterday'),
                'ViewerCode' => 'viewer-code',
                'SiriusId' => '12345678901'
            ]);

        $activityRepository = $this->prophesize(ViewerCodeActivityInterface::class);

        $apiClient = $this->prophesize(ClientInterface::class);

        $instance = new LpaService(
            $viewerRepository->reveal(),
            $activityRepository->reveal(),
            $apiClient->reveal()
        );

        $this->expectException(GoneException::class);

        $lpa = $instance->getByCode('share-code');
    }
}