<?php

declare(strict_types=1);

namespace ViewerTest\Validator;

use PHPUnit\Framework\TestCase;
use Viewer\Validator\CsrfGuardValidator;
use InvalidArgumentException;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use stdClass;

class CsrfGuardValidatorTest extends TestCase
{
    public function testThrowsExceptionWhenNoGuardOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new CsrfGuardValidator();
    }

    public function testThrowsExceptionWhenIncorrectGuardPassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new CsrfGuardValidator([
            'guard' => new stdClass()
        ]);
    }

    public function testIsValidWhenShouldBe()
    {
        $guard = $this->prophesize(CsrfGuardInterface::class);
        $guard->validateToken("token")->willReturn(true);

        $validator = new CsrfGuardValidator([
            'guard' => $guard->reveal()
        ]);

        $this->assertTrue($validator->isValid("token"));
    }

    public function testIsNotValidWhenShouldntBe()
    {
        $guard = $this->prophesize(CsrfGuardInterface::class);
        $guard->validateToken("token")->willReturn(false);

        $validator = new CsrfGuardValidator([
            'guard' => $guard->reveal()
        ]);

        $this->assertFalse($validator->isValid("token"));
        $this->assertArrayHasKey(CsrfGuardValidator::NOT_SAME, $validator->getMessages());
    }
}
