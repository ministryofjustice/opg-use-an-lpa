<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;

class PdfResponse extends Response
{
    public function __construct(StreamInterface $pdfStream, string $filename, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/pdf';
        $headers['Content-Disposition'] = 'attachment; filename=' . $filename . '.pdf';

        parent::__construct($pdfStream, $status, $headers);
    }
}
