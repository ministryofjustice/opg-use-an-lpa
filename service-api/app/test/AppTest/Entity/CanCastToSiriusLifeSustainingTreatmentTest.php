<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\CastToSiriusLifeSustainingTreatment;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanCastToSiriusLifeSustainingTreatmentTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToSiriusLifeSustainingTreatment $castToSiriusLifeSustainingTreatment;

    public function setUp(): void
    {
        $this->mockHydrator                        = $this->createMock(ObjectMapper::class);
        $this->castToSiriusLifeSustainingTreatment = new CastToSiriusLifeSustainingTreatment();
    }

    #[Test]
    public function can_cast_sirius_life_sustaining_treatment(): void
    {
        $lifeSustainingTreatment = 'Option A';

        $expectedLifeSustainingTreatment = 'option-a';

        $result = $this->castToSiriusLifeSustainingTreatment->cast($lifeSustainingTreatment, $this->mockHydrator);

        $this->assertEquals($expectedLifeSustainingTreatment, $result);
    }
}
