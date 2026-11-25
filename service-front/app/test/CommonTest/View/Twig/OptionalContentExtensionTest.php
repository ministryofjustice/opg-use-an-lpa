<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\OptionalContentExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

class OptionalContentExtensionTest extends TestCase
{
    #[Test]
    public function it_provides_a_no_hint_test(): void
    {
        $tests = (new OptionalContentExtension())->getTests();

        $this->assertCount(1, $tests);
        $this->assertEquals('no_hint', $tests[0]->getName());
    }

    #[Test]
    #[DataProvider('hintValues')]
    public function it_identifies_the_no_hint_marker(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, (new OptionalContentExtension())->isNoHint($value));
    }

    public static function hintValues(): array
    {
        return [
            'marker'                 => [
                '[no-hint]',
                true,
            ],
            'marker with whitespace' => [
                "\n    [no-hint]\n",
                true,
            ],
            'captured marker'        => [
                new Markup("\n    [no-hint]\n", 'UTF-8'),
                true,
            ],
            'hint text'              => [
                'Include any middle names.',
                false,
            ],
            'untranslated key'       => [
                'feature.page.field-name.hint',
                false,
            ],
            'empty string'           => [
                '',
                false,
            ],
            'null'                   => [
                null,
                false,
            ],
        ];
    }

    #[Test]
    public function it_can_be_used_in_a_template(): void
    {
        $twig = new Environment(
            new ArrayLoader(['t' => '{% if hint is not no_hint %}{{ hint }}{% endif %}'])
        );
        $twig->addExtension(new OptionalContentExtension());

        $this->assertSame('', $twig->render('t', ['hint' => '[no-hint]']));
        $this->assertSame('A hint', $twig->render('t', ['hint' => 'A hint']));
        $this->assertSame(
            '',
            $twig->render('t', ['hint' => new Markup('[no-hint]', 'UTF-8')])
        );
    }
}
