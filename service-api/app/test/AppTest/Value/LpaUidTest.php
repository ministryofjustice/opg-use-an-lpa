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
    public function it_correctly_identifies_the_lpa_type(string $uid, LpaSource $source): void
    {
        $sut = new LpaUid($uid);

        $this->assertEquals($source, $sut->getLpaSource());
    }

    public static function lpaData(): array
    {
        return [
            'sirius type'    => ['700000000047', LpaSource::SIRIUS],
            'lpa store type' => ['M-7890-0400-4003', LpaSource::LPASTORE],
        ];
    }
}
