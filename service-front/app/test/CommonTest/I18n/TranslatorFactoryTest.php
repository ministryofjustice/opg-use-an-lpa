<?php

declare(strict_types=1);

namespace CommonTest\I18n;

use Common\I18n\TranslatorFactory;
use Laminas\I18n\Translator\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TranslatorFactoryTest extends TestCase
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
                        'translation_files' => [
                            [
                                'type'     => \Laminas\I18n\Translator\Loader\Gettext::class,
                                'filename' => '/app/languages/cy.mo',
                                'locale'   => 'cy'
                            ]
                        ],
                    ]
                ]
            );

        $factory = new TranslatorFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Translator::class, $instance);

        $this->assertEquals(
            'Enter code',
            $instance->translate('Enter code', 'default', 'en_GB')
        );
        $this->assertEquals(
            'Rhowch god',
            $instance->translate('Enter code', 'default', 'cy')
        );
        $this->assertEquals(
            'A oes gennych gyfrif pŵer atwrnai parhaol?',
            $instance->translate('Do you have a Use a lasting power of attorney account?', 'default', 'cy')
        );
        $this->assertEquals(
            'Os ydych wedi\'ch enwi ar fwy nag un ACLL, gallwch ychwanegu\'r holl ACLlau hynny at eich cyfrif.',
            $instance->translate('If you\'re named on more than one LPA, you can add all those LPAs to your account.', 'default', 'cy')
        );
    }

    /** @test */
    public function it_needs_language_file_configuration(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new TranslatorFactory();

        $this->expectException(\RuntimeException::class);
        $instance = $factory($containerProphecy->reveal());
    }
}
