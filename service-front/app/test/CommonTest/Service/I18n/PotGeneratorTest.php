<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Common\Service\I18n\PotGenerator;
use Gettext\Generator\GeneratorInterface;
use Gettext\Headers;
use Gettext\Translations;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class PotGeneratorTest extends TestCase
{
    /** @test */
    public function it_will_write_out_a_catalogue(): void
    {
        $headersProphecy = $this->prophesize(Headers::class);
        $headersProphecy->setLanguage('en_GB')->willReturn($headersProphecy->reveal());
        $headersProphecy->set(
            'POT-Creation-Date',
            Argument::that(
                function (string $date) {
                    $datetime = new \DateTime($date);
                    $this->assertInstanceOf(\DateTime::class, $datetime);

                    return true;
                }
            )
        );

        $catalogueProphecy = $this->prophesize(Translations::class);
        $catalogueProphecy
            ->getHeaders()
            ->willReturn($headersProphecy->reveal());

        $writerProphecy = $this->prophesize(GeneratorInterface::class);
        $writerProphecy
            ->generateFile($catalogueProphecy->reveal(), 'languages/messages.pot')
            ->willReturn(true);

        $generator = new PotGenerator($writerProphecy->reveal());

        $count = $generator->generate(['messages' => $catalogueProphecy->reveal()]);

        $this->assertEquals(1, $count);
    }
}
