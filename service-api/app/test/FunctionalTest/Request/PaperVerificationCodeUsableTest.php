<?php

declare(strict_types=1);

namespace FunctionalTest\Request;

use App\Exception\BadRequestException;
use App\Middleware\RequestObject\RequestParser;
use App\Request\PaperVerificationCodeUsable;
use FunctionalTest\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

class PaperVerificationCodeUsableTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function it_is_created_successfully(): void
    {
        $data = [
            'code' => 'P-1111-1111-1111-11',
            'name' => 'Test',
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        $object = $request->get(PaperVerificationCodeUsable::class);

        $this->assertEquals($data['code'], $object->code);
        $this->assertEquals($data['name'], $object->name);
    }

    #[Test]
    public function code_must_be_correct_format(): void
    {
        $data = [
            'code' => '1111-1111-1111',
            'name' => 'Test',
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        try {
            $request->get(PaperVerificationCodeUsable::class);
        } catch (BadRequestException $e) {
            $this->assertArrayHasKey('code', $e->getAdditionalData());
        }
    }
}
