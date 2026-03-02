<?php

declare(strict_types=1);

namespace ActorTest\Handler\Traits;

use Actor\Handler\ResolveLpaTypeTrait\LpaTypeResolver;
use Common\Service\Log\EventCodes;
use PHPUnit\Framework\TestCase;
use Acpr\I18n\TranslatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class LpaTypeResolverTest extends TestCase
{
    private object $resolver;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->resolver = new class ($this->translator) {
            use LpaTypeResolver;

            public function __construct(public TranslatorInterface $translator)
            {
            }
        };
    }

    #[DataProvider('resolveLabelProvider')]
    public function testResolveLabel(
        string $subtype,
        string $uid,
        string $expectedTranslation,
    ): void {
        $this->translator
            ->expects($this->once())
            ->method('translate')
            ->with($expectedTranslation)
            ->willReturn($expectedTranslation);

        $result = $this->resolver->resolveLabel($subtype, $uid);

        $this->assertSame($expectedTranslation, $result);
    }

    public static function resolveLabelProvider(): array
    {
        return [
            [
                'hw',
                'M-123456789',
                'personal welfare',
            ],
            [
                'hw',
                '7700000000001',
                'health and welfare',
            ],
            [
                'pfa',
                'M-987654321',
                'property and affairs',
            ],
            [
                'pfa',
                '700000000002',
                'property and finance',
            ],
            [
                'HW',
                'M-123456789',
                'personal welfare',
            ],
        ];
    }

    #[DataProvider('resolveEventCodeProvider')]
    public function testResolveEventCode(
        string $subtype,
        string $expectedEventCode,
    ): void {
        $result = $this->resolver->resolveEventCode($subtype);

        $this->assertSame($expectedEventCode, $result);
    }

    public static function resolveEventCodeProvider(): array
    {
        return [
            [
                'hw',
                EventCodes::ADDED_LPA_TYPE_HW,
            ],
            [
                'pfa',
                EventCodes::ADDED_LPA_TYPE_PFA,
            ],
            [
                'HW',
                EventCodes::ADDED_LPA_TYPE_HW,
            ],
        ];
    }
}
