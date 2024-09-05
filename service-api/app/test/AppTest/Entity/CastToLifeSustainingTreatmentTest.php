<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToLifeSustainingTreatment;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToLifeSustainingTreatmentTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToLifeSustainingTreatment $castToLifeSustainingTreatment;

    public function setUp(): void
    {
        $this->mockHydrator                  = $this->createMock(ObjectMapper::class);
        $this->castToLifeSustainingTreatment = new CastToLifeSustainingTreatment();
    }

    #[Test]
    public function can_cast_life_sustaining_treatment(): void
    {
        $lifeSustainingTreatment = 'option-a';

        $expectedLifeSustainingTreatment = 'option-a';

        $result = $this->castToLifeSustainingTreatment->cast($lifeSustainingTreatment, $this->mockHydrator);

        $this->assertEquals($expectedLifeSustainingTreatment, $result);
    }
}
