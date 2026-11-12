<?php

declare(strict_types=1);

namespace ActorTest\Handler\RequestActivationKey;

use Actor\Handler\RequestActivationKey\ReferenceNumberHandler;
use Actor\Workflow\RequestActivationKey;
use Common\Service\Features\FeatureEnabled;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class ReferenceNumberHandlerTest extends TestCase
{
    use ProphecyTrait;

    private ReferenceNumberHandler $sut;

    public function setUp(): void
    {
        $this->sut = new ReferenceNumberHandler(
            $this->prophesize(TemplateRendererInterface::class)->reveal(),
            $this->prophesize(UrlHelper::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(FeatureEnabled::class)->reveal(),
        );
    }

    public static function states(): array
    {
        return [
            'first time through the workflow' => [
                [],
                'lpa.your-name',
                'lpa.add-by-paper-information',
            ],
            'changing reference number from check your answers' => [
                ['postcode' => 'PO3 6BT'],
                'lpa.check-answers',
                'lpa.check-answers',
            ],
            'requesting another key with details already given' => [
                ['postcode' => 'PO3 6BT', 'requestingAnother' => true],
                'lpa.your-name',
                'lpa.add-by-paper-information',
            ],
        ];
    }

    #[DataProvider('states')]
    #[Test]
    public function it_decides_the_next_and_last_pages(array $state, string $next, string $last): void
    {
        $workflow = new RequestActivationKey(...$state);

        $this->assertSame($next, $this->sut->nextPage($workflow));
        $this->assertSame($last, $this->sut->lastPage($workflow));
    }
}
