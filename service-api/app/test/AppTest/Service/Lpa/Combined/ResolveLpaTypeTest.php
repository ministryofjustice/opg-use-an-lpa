<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\Combined;

use App\Service\Lpa\Combined\ResolveLpaTypes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ResolveLpaTypeTest extends TestCase
{
    use ProphecyTrait;

    private ResolveLpaTypes $resolveLpaTypes;

    protected function setUp(): void
    {
        $this->resolveLpaTypes = new ResolveLpaTypes();
    }

    #[Test]
    public function it_correctly_splits_sirius_and_datastore_uids(): void
    {
        $lpaActorMaps = [
            ['SiriusUid' => '700000000001', 'LpaUid' => 'A12345678901'],
            ['SiriusUid' => '800000000002', 'LpaUid' => 'B12345678901'],
            ['SiriusUid' => '700000000003', 'LpaUid' => 'C12345678901'],
            ['SiriusUid' => '600000000004', 'LpaUid' => 'D12345678901'],
        ];

        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $this->assertEquals([0 => '700000000001', 2 => '700000000003'], $siriusUids);
        $this->assertEquals(
            ['800000000002', '600000000004', 'A12345678901', 'B12345678901', 'C12345678901', 'D12345678901'],
            $datastoreUids
        );
    }

    #[Test]
    public function it_handles_only_sirius_uids(): void
    {
        $lpaActorMaps = [
            ['SiriusUid' => '700000000001'],
            ['SiriusUid' => '700000000002'],
        ];

        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $this->assertEquals(['700000000001', '700000000002'], $siriusUids);
        $this->assertEmpty($datastoreUids);
    }

    #[Test]
    public function it_handles_only_datastore_uids(): void
    {
        $lpaActorMaps = [
            ['SiriusUid' => '800000000001', 'LpaUid' => 'A12345678901'],
            ['SiriusUid' => '900000000002', 'LpaUid' => 'B12345678901'],
        ];

        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $this->assertEmpty($siriusUids);
        $this->assertEquals(['800000000001', '900000000002', 'A12345678901', 'B12345678901'], $datastoreUids);
    }

    #[Test]
    public function it_handles_empty_input(): void
    {
        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)([]);

        $this->assertEmpty($siriusUids);
        $this->assertEmpty($datastoreUids);
    }
}
