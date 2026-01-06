<?php

declare(strict_types=1);

namespace AppTest\Value;

use App\Enum\LpaSource;
use App\Value\LpaUid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaUidTest extends TestCase
{
    #[Test]
    #[DataProvider('lpaData')]
    public function it_correctly_identifies_the_lpa_type(string $input, string $lpaUid, LpaSource $source): void
    {
        $sut = new LpaUid($input);

        $this->assertEquals($lpaUid, $sut->getLpaUid());
        $this->assertEquals($source, $sut->getLpaSource());
    }

    public static function lpaData(): array
    {
        return [
            'sirius type'                => [
                '700000000047',
                '700000000047',
                LpaSource::SIRIUS,
            ],
            'lpa store type'             => [
                'M-7890-0400-4000',
                'M-7890-0400-4000',
                LpaSource::LPASTORE,
            ],
            'lpa store type unformatted' => [
                'M789004004000',
                'M-7890-0400-4000',
                LpaSource::LPASTORE,
            ],
        ];
    }
}
