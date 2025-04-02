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
            ['SiriusUid' => '700000000001', 'LpaUid' => 'M-A123-4567-8901'],
            ['SiriusUid' => 'M-8000-0000-0002', 'LpaUid' => 'M-B123-4567-8901'],
            ['SiriusUid' => '700000000003', 'LpaUid' => 'M-C123-4567-8901'],
            ['SiriusUid' => 'M-6000-0000-0004', 'LpaUid' => 'M-D123-4567-8901'],
        ];

        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $this->assertEquals(['700000000001', '700000000003'], $siriusUids);
        $this->assertEquals(
            [
                'M-8000-0000-0002',
                'M-6000-0000-0004',
                'M-A123-4567-8901',
                'M-B123-4567-8901',
                'M-C123-4567-8901',
                'M-D123-4567-8901',
            ],
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
            ['SiriusUid' => 'M-8000-0000-0001', 'LpaUid' => 'M-A123-4567-8901'],
            ['SiriusUid' => 'M-9000-0000-0002', 'LpaUid' => 'M-B123-4567-8901'],
        ];

        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $this->assertEmpty($siriusUids);
        $this->assertEquals(
            [
                'M-8000-0000-0001',
                'M-9000-0000-0002',
                'M-A123-4567-8901',
                'M-B123-4567-8901',
            ],
            $datastoreUids
        );
    }

    #[Test]
    public function it_handles_empty_input(): void
    {
        [$siriusUids, $datastoreUids] = ($this->resolveLpaTypes)([]);

        $this->assertEmpty($siriusUids);
        $this->assertEmpty($datastoreUids);
    }
}
