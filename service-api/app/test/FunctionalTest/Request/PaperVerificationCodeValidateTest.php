<?php

declare(strict_types=1);

namespace FunctionalTest\Request;

use App\Exception\BadRequestException;
use App\Middleware\RequestObject\RequestParser;
use App\Request\PaperVerificationCodeValidate;
use DateTimeImmutable;
use FunctionalTest\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

class PaperVerificationCodeValidateTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function it_is_created_successfully(): void
    {
        $data = [
            'name'          => 'Test',
            'code'          => 'P-1111-1111-1111-11',
            'lpaUid'        => 'M-1234-1234-1234',
            'sentToDonor'   => false,
            'attorneyName'  => 'Test Test',
            'dateOfBirth'   => '1999-01-01',
            'noOfAttorneys' => 2,
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        $object = $request->get(PaperVerificationCodeValidate::class);

        $this->assertEquals($data['code'], $object->code);
        $this->assertEquals($data['name'], $object->name);
        $this->assertEquals($data['lpaUid'], $object->lpaUid);
        $this->assertEquals($data['sentToDonor'], $object->sentToDonor);
        $this->assertEquals($data['attorneyName'], $object->attorneyName);
        $this->assertEquals(new DateTimeImmutable($data['dateOfBirth']), $object->dateOfBirth);
        $this->assertEquals($data['noOfAttorneys'], $object->noOfAttorneys);
    }

    #[Test]
    public function code_must_be_correct_format(): void
    {
        $data = [
            'name'          => 'Test',
            'code'          => '1111-1111-1111',
            'lpaUid'        => 'M-1234-1234-1234',
            'sentToDonor'   => false,
            'attorneyName'  => 'Test Test',
            'dateOfBirth'   => '1999-01-01',
            'noOfAttorneys' => 2,
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        try {
            $request->get(PaperVerificationCodeValidate::class);
        } catch (BadRequestException $e) {
            $this->assertCount(1, $e->getAdditionalData());
            $this->assertArrayHasKey('code', $e->getAdditionalData());
        }
    }

    #[Test]
    public function sent_to_donor_is_proper_boolean(): void
    {
        $data = [
            'name'          => 'Test',
            'code'          => 'P-1111-1111-1111-11',
            'lpaUid'        => 'M-1234-1234-1234',
            'sentToDonor'   => 'false',
            'attorneyName'  => 'Test Test',
            'dateOfBirth'   => '1999-01-01',
            'noOfAttorneys' => 2,
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        try {
            $request->get(PaperVerificationCodeValidate::class);
        } catch (BadRequestException $e) {
            $this->assertArrayHasKey('sentToDonor', $e->getAdditionalData());
        }
    }

    #[Test]
    public function date_is_in_correct_format(): void
    {
        $data = [
            'name'          => 'Test',
            'code'          => 'P-1111-1111-1111-11',
            'lpaUid'        => 'M-1234-1234-1234',
            'sentToDonor'   => false,
            'attorneyName'  => 'Test Test',
            'dateOfBirth'   => '01/01/2001',
            'noOfAttorneys' => 2,
        ];

        $requestParser = $this->container->get(RequestParser::class);
        $request       = $requestParser->setRequestData($data);

        try {
            $request->get(PaperVerificationCodeValidate::class);
        } catch (BadRequestException $e) {
            $this->assertArrayHasKey('dateOfBirth', $e->getAdditionalData());
        }
    }
}
