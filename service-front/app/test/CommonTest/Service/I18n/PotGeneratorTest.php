<?php

declare(strict_types=1);

namespace CommonTest\Service\I18n;

use Common\Service\I18n\PotGenerator;
use DateTime;
use Gettext\Generator\GeneratorInterface;
use Gettext\Headers;
use Gettext\Translation;
use Gettext\Translations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class PotGeneratorTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_will_write_out_a_catalogue(): void
    {
        $testTranslation = Translation::create(null, 'Test string');
        $testTranslation->getReferences()
            ->add('test/file.php', 12)
            ->add('test/anotherFile.php', 20)
            ->add('test/yetAnotherFile.php');

        $headersProphecy = $this->prophesize(Headers::class);
        $headersProphecy->setLanguage('en_GB')->willReturn($headersProphecy->reveal());
        $headersProphecy->set(
            'POT-Creation-Date',
            Argument::that(
                function (string $date) {
                    $datetime = new DateTime($date);
                    $this->assertInstanceOf(DateTime::class, $datetime);

                    return true;
                }
            )
        );

        $catalogueProphecy = $this->prophesize(Translations::class);
        $catalogueProphecy
            ->getHeaders()
            ->willReturn($headersProphecy->reveal());
        $catalogueProphecy
            ->getTranslations()
            ->willReturn(
                [
                    $testTranslation,
                ]
            );
        $catalogueProphecy
            ->addOrMerge(
                Argument::that(
                    function (Translation $translation) {
                        // check that sorting happened as expected
                        $this->assertSame(
                            'test/anotherFile.php',
                            $translation->getReferences()->getIterator()->key()
                        );

                        return true;
                    }
                ),
                Argument::any()
            )
            ->shouldBeCalled();

        $writerProphecy = $this->prophesize(GeneratorInterface::class);
        $writerProphecy
            ->generateFile($catalogueProphecy->reveal(), 'languages/messages.pot')
            ->willReturn(true);

        $generator = new PotGenerator($writerProphecy->reveal());

        $count = $generator->generate(['messages' => $catalogueProphecy->reveal()]);

        $this->assertEquals(1, $count);
    }
}
