<?php

declare(strict_types=1);

namespace CommonTest\I18n;

use Common\I18n\SymfonyTranslatorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Translator;

class SymfonyTranslatorFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_a_welsh_translator_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'i18n' => [
                        'default_locale' => 'en_GB',
                        'languages' => [
                            'welsh' => [
                                'format' => 'mo',
                                'resource' => '/app/languages/cy.mo',
                                'locale' => 'cy'
                            ]
                        ]
                    ],
                ]
            );

        $factory = new SymfonyTranslatorFactory();

        /** @var Translator $instance */
        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Translator::class, $instance);

        $this->assertEquals(
            'LPA access code',
            $instance->trans('LPA access code', [], 'messages', 'en_GB')
        );
        $this->assertEquals(
            'Cod mynediad yr LPA',
            $instance->trans('LPA access code', [], 'messages', 'cy')
        );
    }

    /** @test */
    public function it_needs_language_file_configuration(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new SymfonyTranslatorFactory();

        $this->expectException(\RuntimeException::class);
        $instance = $factory($containerProphecy->reveal());
    }
}
