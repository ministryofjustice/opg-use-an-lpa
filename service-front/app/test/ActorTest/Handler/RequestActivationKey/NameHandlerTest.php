<?php

declare(strict_types=1);

namespace ActorTest\Handler\RequestActivationKey;

use Actor\Handler\RequestActivationKey\NameHandler;
use Actor\Workflow\RequestActivationKey;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class NameHandlerTest extends TestCase
{
    use ProphecyTrait;

    private NameHandler $sut;

    public function setUp(): void
    {
        $this->sut = new NameHandler(
            $this->prophesize(TemplateRendererInterface::class)->reveal(),
            $this->prophesize(UrlHelper::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );
    }

    private static function personalDetails(): array
    {
        return [
            'firstNames'      => 'John Henry',
            'lastName'        => 'Smith',
            'dob'             => '1955-11-05T00:00:00+00:00',
            'postcode'        => 'PO3 6BT',
            'liveInUK'        => 'Yes',
            'referenceNumber' => '700000000138',
        ];
    }

    public static function nextPageStates(): array
    {
        $details = self::personalDetails();

        return [
            'first time through the workflow' => [
                ['firstNames' => 'John Henry', 'lastName' => 'Smith', 'referenceNumber' => '700000000138'],
                'lpa.date-of-birth',
            ],
            'date of birth answered but no address yet' => [
                array_merge($details, ['postcode' => null, 'liveInUK' => null]),
                'lpa.date-of-birth',
            ],
            'requesting another key with details already given' => [
                array_merge($details, ['requestingAnother' => true]),
                'lpa.check-answers',
            ],
            'changing name from check your answers' => [
                $details,
                'lpa.check-answers',
            ],
        ];
    }

    #[DataProvider('nextPageStates')]
    #[Test]
    public function it_decides_the_next_page(array $state, string $expected): void
    {
        $this->assertSame($expected, $this->sut->nextPage(new RequestActivationKey(...$state)));
    }

    #[Test]
    public function it_skips_to_check_answers_after_a_request_another_reset(): void
    {
        $state = new RequestActivationKey(...self::personalDetails());
        $state->resetForAnotherRequest();

        $this->assertSame('lpa.check-answers', $this->sut->nextPage($state));
    }

    #[Test]
    public function it_does_not_skip_after_a_start_again_reset(): void
    {
        $state = new RequestActivationKey(...self::personalDetails());
        $state->reset();

        $this->assertSame('lpa.date-of-birth', $this->sut->nextPage($state));
    }

    public static function lastPageStates(): array
    {
        $details = self::personalDetails();

        return [
            'first time through the workflow' => [
                ['referenceNumber' => '700000000138'],
                'lpa.add-by-paper',
            ],
            'requesting another key' => [
                array_merge($details, ['requestingAnother' => true]),
                'lpa.add-by-paper',
            ],
            'changing name from check your answers' => [
                $details,
                'lpa.check-answers',
            ],
        ];
    }

    #[DataProvider('lastPageStates')]
    #[Test]
    public function it_decides_the_last_page(array $state, string $expected): void
    {
        $this->assertSame($expected, $this->sut->lastPage(new RequestActivationKey(...$state)));
    }
}
