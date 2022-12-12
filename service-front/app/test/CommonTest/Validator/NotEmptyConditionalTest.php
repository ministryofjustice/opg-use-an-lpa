<?php

declare(strict_types=1);

namespace CommonTest\Validator;

use Common\Validator\NotEmptyConditional;
use Mezzio\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class NotEmptyConditionalTest extends TestCase
{
    private NotEmptyConditional $validator;

    public function setUp(): void
    {
        $this->validator = new NotEmptyConditional(
            [
                NotEmptyConditional::DEPENDANT_KEY       => 'live_in_uk',
                NotEmptyConditional::DEPENDANT_VALUE_KEY => 'Yes',
            ]
        );
    }

    /** @test */
    public function isValidWhenDependantIsExpectedValue(): void
    {
        $isValid = $this->validator->isValid(
            '',
            [
                'live_in_uk' => 'Yes',
            ]
        );

        $this->assertTrue($isValid);
    }

    /** @test */
    public function isValidWhenNotEmpty(): void
    {
        $isValid = $this->validator->isValid(
            'ABC123',
            [
                'live_in_uk' => 'No',
            ]
        );

        $this->assertTrue($isValid);
    }

    /** @test */
    public function notValidWhenEmptyAndNoDependant(): void
    {
        $isValid = $this->validator->isValid(
            '',
            [
                 'live_in_uk' => 'No',
             ]
        );

        $this->assertFalse($isValid);
    }

    /** @test */
    public function noMessageWhenDependantValueIsNull(): void
    {
        $isValid = $this->validator->isValid(
            '',
            [
                'live_in_uk' => null,
            ]
        );

        $this->assertFalse($isValid);

        self::assertEmpty($this->validator->getMessages());
    }

    /** @test */
    public function errorWhenNoDependantConfigured(): void
    {
        $this->validator = new NotEmptyConditional([]);
        $this->expectException(RuntimeException::class);
        $this->validator->isValid('ABC123');
    }
}
