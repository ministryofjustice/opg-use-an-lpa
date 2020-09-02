<?php

declare(strict_types=1);

namespace CommonTest\I18n;

use Acpr\I18n\TranslatorInterface;
use Common\I18n\TranslatorFactory;
use Gettext\GettextTranslator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TranslatorFactoryTest extends TestCase
{
    /** @test
     */
    public function it_returns_a_welsh_translator_instance(): void
    {
        $gettextTranslator = $this->prophesize(GettextTranslator::class);
        $gettextTranslator->setLanguage('en_GB')->willReturn($gettextTranslator->reveal());
        $gettextTranslator->loadDomain('messages', '/app/languages/')->willReturn($gettextTranslator->reveal());

        $gettextTranslator->gettext('LPA access code')->willReturn('LPA access code');

        $gettextTranslator->setLanguage('cy')->will(function($language) {
            $this->gettext('LPA access code')->willReturn('Rhowch god mynediad yr ACLL');
            return $this->reveal();
        });

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'i18n' => [
                        'default_locale' => 'en_GB',
                        'default_domain' => 'messages',
                        'locale_path' => '/app/languages/'
                    ],
                ]
            );
        $containerProphecy->get(GettextTranslator::class)
            ->willReturn($gettextTranslator);

        $factory = new TranslatorFactory();

        /** @var TranslatorInterface $instance */
        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(TranslatorInterface::class, $instance);

        $this->assertEquals(
            'LPA access code',
            $instance->translate('LPA access code')
        );

        $instance->setLocale('cy');
        $this->assertEquals(
            'Rhowch god mynediad yr ACLL',
            $instance->translate('LPA access code')
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
