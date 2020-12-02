<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway\Sanitisers;

use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use PHPUnit\Framework\TestCase;

class SiriusLpaSanitiserTest extends TestCase
{
    /** @test */
    public function it_removes_hyphens_from_sirius_uids(): void
    {
        $originalLpa = [
            'uId' => '7000-0000-0054',
            'donor' => [
                'uId' => '7000-0000-0054',
                'linked' => [
                    [
                        'uId' => '7000-0000-0054'
                    ],
                    [
                        'uId' => '7000-0000-0054'
                    ]
                ]
            ],
            'attorneys' => [
                [
                    'uId' => '7000-0000-0054'
                ]
            ]
        ];

        $cleanedLpa = [
            'uId' => '700000000054',
            'donor' => [
                'uId' => '700000000054',
                'linked' => [
                    [
                        'uId' => '700000000054'
                    ],
                    [
                        'uId' => '700000000054'
                    ]
                ]
            ],
            'attorneys' => [
                [
                    'uId' => '700000000054'
                ]
            ]
        ];

        $sanitiser = new SiriusLpaSanitiser();

        $lpa = $sanitiser->sanitise($originalLpa);

        $this->assertEquals($cleanedLpa, $lpa);
    }
}
