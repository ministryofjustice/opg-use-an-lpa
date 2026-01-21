<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;
use App\Enum\InstructionsAndPreferencesImagesResult;
use App\Service\Lpa\GetInstructionsAndPreferencesImages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(GetInstructionsAndPreferencesImages::class)]
class GetInstructionsAndPreferencesImagesTest extends TestCase
{
    #[Test]
    public function it_can_be_initialised(): void
    {
        $repositoryStub = $this->createStub(InstructionsAndPreferencesImagesInterface::class);
        $loggerStub     = $this->createStub(LoggerInterface::class);

        $sut = new GetInstructionsAndPreferencesImages($repositoryStub, $loggerStub);

        $this->assertInstanceOf(GetInstructionsAndPreferencesImages::class, $sut);
    }

    #[Test]
    public function it_returns_images_when_passed_a_uid(): void
    {
        $images = new InstructionsAndPreferencesImages(
            700000000001,
            InstructionsAndPreferencesImagesResult::COLLECTION_COMPLETE,
            [
                'iap-700000000001-instructions' => 'https://image-url',
            ],
        );

        $repositoryMock = $this->createMock(InstructionsAndPreferencesImagesInterface::class);
        $repositoryMock
            ->expects($this->once())
            ->method('getInstructionsAndPreferencesImages')
            ->with($this->equalTo(700000000001))
            ->willReturn($images);
        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new GetInstructionsAndPreferencesImages($repositoryMock, $loggerStub);

        $result = ($sut)(700000000001);

        $this->assertEquals($images, $result);
    }

    #[Test]
    #[DataProvider('imagesCollectionStatus')]
    public function it_logs_an_event_when_extraction_is_not_a_success(
        InstructionsAndPreferencesImagesResult $status,
    ): void {
        $images = new InstructionsAndPreferencesImages(
            700000000001,
            $status,
            [],
        );

        $repositoryMock = $this->createMock(InstructionsAndPreferencesImagesInterface::class);
        $repositoryMock
            ->expects($this->once())
            ->method('getInstructionsAndPreferencesImages')
            ->with($this->equalTo(700000000001))
            ->willReturn($images);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('notice')
            ->with($this->anything());

        $sut = new GetInstructionsAndPreferencesImages($repositoryMock, $loggerMock);

        $result = ($sut)(700000000001);

        $this->assertEquals($images, $result);
    }

    public static function imagesCollectionStatus(): iterable
    {
        yield [InstructionsAndPreferencesImagesResult::COLLECTION_NOT_STARTED];
        yield [InstructionsAndPreferencesImagesResult::COLLECTION_ERROR];
    }
}
