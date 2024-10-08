<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Pdf\PdfResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\StreamInterface;

class PdfResponseTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_is_a_custom_response_that_sets_pdf_headers(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);

        $response = new PdfResponse($streamProphecy->reveal(), 'testFilename');

        $this->assertStringContainsString('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('testFilename', $response->getHeaderLine('Content-Disposition'));
    }
}
