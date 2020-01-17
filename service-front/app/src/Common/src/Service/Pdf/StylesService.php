<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use RuntimeException;

use function file_get_contents;

class StylesService
{
    /**
     * @var string
     */
    private $stylePath;

    public function __construct(string $stylePath = './assets/pdf.css')
    {
        $this->stylePath = $stylePath;
    }

    public function __invoke(): string
    {
        $styles = @file_get_contents($this->stylePath);

        if ($styles === false) {
            throw new RuntimeException('PDF styles file "' . $this->stylePath . '" not found');
        }

        return $styles;
    }
}