<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use Common\Service\Pdf\StylesService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StylesServiceTest extends TestCase
{
    /** @test */
    public function it_returns_styles_from_a_file(): void
    {
        $fs = vfsStream::setup(null, null, ['styles.css' => '/* css rules */']);

        $stylesService = new StylesService($fs->getChild('styles.css')->url());

        $css = $stylesService();

        $this->assertEquals('/* css rules */', $css);
    }

    /** @test */
    public function it_throws_exception_if_styles_not_loaded(): void
    {
        $stylesService = new StylesService('/wont_exist_probably');

        $this->expectException(RuntimeException::class);
        $css = $stylesService();
    }
}
