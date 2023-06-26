<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\Factory\InstAndPrefImages;
use Common\Service\Lpa\InstAndPrefImagesService;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class InstAndPrefImagesServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|Client $apiClientProphecy;
    private ObjectProphecy|LoggerInterface $loggerProphecy;
    private InstAndPrefImagesService $instAndPrefImagesService;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);
        $this->loggerProphecy    = $this->prophesize(LoggerInterface::class);

        $this->instAndPrefImagesService = new InstAndPrefImagesService(
            $this->apiClientProphecy->reveal(),
            new InstAndPrefImages(),
            $this->loggerProphecy->reveal(),
        );
    }

    /** @test */
    public function it_gets_a_list_of_lpas_for_a_user()
    {
        $userToken = 'i-am-a-user-token';

        $actorLpaToken = '01234567-01234-01234-01234-012345678901';

        $uId = 123;

        $imageDataFromApi = [
            'uId'        => $uId,
            'status'     => 'COLLECTION_COMPLETE',
            'signedUrls' => [
                'iap-' . $uId . '-instructions' => 'http://www.example.com/image1.jpg',
                'iap-' . $uId . '-preferences'  => 'http://www.example.com/image2.jpg',
            ],
        ];

        $this->apiClientProphecy->httpGet('/v1/lpas/' . $actorLpaToken . '/images')->willReturn($imageDataFromApi);
        $this->apiClientProphecy->setUserTokenHeader($userToken)->shouldBeCalled();

        $images = $this->instAndPrefImagesService->getImagesById($userToken, $actorLpaToken);

        $this->assertInstanceOf(Images::class, $images);
        $this->assertEquals(ImagesStatus::COLLECTION_COMPLETE, $images->status);

        $instructionsUrls = $images->getInstructionsImageUrls();
        $this->assertCount(1, $instructionsUrls);
        $this->assertEquals('http://www.example.com/image1.jpg', $instructionsUrls[0]->url);

        $preferencesUrls = $images->getPreferencesImageUrls();
        $this->assertCount(1, $preferencesUrls);
        $this->assertEquals('http://www.example.com/image2.jpg', $preferencesUrls[0]->url);
    }
}
