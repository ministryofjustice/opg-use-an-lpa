<?php

declare(strict_types=1);

namespace FunctionalTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\Response\ActorCodeIsValid;
use App\Service\Log\RequestTracing;
use FunctionalTest\AbstractFunctionalTestCase;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerConfig;
use PHPUnit\Framework\Attributes\Test;

class ActorCodesTest extends AbstractFunctionalTestCase
{
    private InteractionBuilder $builder;

    public function setUp(): void
    {
        parent::setUp();

        $config                          = $this->container->get('config');
        $config['codes_api']['endpoint'] = 'http://lpa-codes-pact-mock';

        $this->containerModifier->setValue(RequestTracing::TRACE_PARAMETER_NAME, 'trace-id');
        $this->containerModifier->setValue('config', $config);

        $mockServer = new MockServerConfig();
        $mockServer->setHost('lpa-codes-pact-mock');
        $mockServer->setPort(80);
        $mockServer->setConsumer('use-an-lpa');
        $mockServer->setProvider('data-lpa-codes');
        $mockServer->setPactDir('./build/pacts');
        $mockServer->setPactFileWriteMode('merge');

        $this->builder = new InteractionBuilder($mockServer);
    }

    public function tearDown(): void
    {
        // ensure the pact contracts are checked and the contracts file is written out
        $this->builder->finalize();
    }

    #[Test]
    public function it_validates_a_code_that_is_valid(): void
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/validate')
            ->setHeaders(
                [
                    'Accept'                          => 'application/vnd.opg-data.v1+json,application/json',
                    'Authorization'                   => $matcher->like('AWS4-HMAC-SHA256'),
                    'Content-Type'                    => 'application/json',
                    RequestTracing::TRACE_HEADER_NAME => $matcher->like('trace-id'),
                ]
            )
            ->setBody(
                [
                    'lpa'  => $matcher->regex('700000000001', '7[0-9]{11}'),
                    'dob'  => $matcher->dateISO8601('1959-08-10'),
                    'code' => $matcher->like('ISAVALIDCODE'),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody(['actor' => $matcher->regex('700000000001', '7[0-9]{11}')]);

        $this->builder
            ->given('the activation key ISAVALIDCODE exists')
            ->uponReceiving('a request to validate the code ISAVALIDCODE')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(ActorCodes::class);

        $actorCode = $sut->validateCode('ISAVALIDCODE', '700000000001', '1959-08-10');

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(ActorCodeIsValid::class, $actorCode->getData());
        self::assertEquals('700000000001', $actorCode->getData()->actorUid);
    }

    #[Test]
    public function it_validates_a_code_that_is_valid_when_paper_verification_code_also_exists(): void
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/validate')
            ->setHeaders(
                [
                    'Accept'                          => 'application/vnd.opg-data.v1+json,application/json',
                    'Authorization'                   => $matcher->like('AWS4-HMAC-SHA256'),
                    'Content-Type'                    => 'application/json',
                    RequestTracing::TRACE_HEADER_NAME => $matcher->like('trace-id'),
                ]
            )
            ->setBody(
                [
                    'lpa'  => $matcher->regex('M-1234-1234-1234', 'M(-[0-9]{4}){3}'),
                    'dob'  => $matcher->dateISO8601('1959-08-10'),
                    'code' => $matcher->like('valid-code'),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'actor'                       => $matcher->uuid('74673c83-05ba-4886-beb1-daaa36fb7984'),
                'has_paper_verification_code' => $matcher->like(true),
            ]);

        $this->builder
            ->given('the provided details match a valid actor code who also has a paper verification code')
            ->uponReceiving('a POST request to /v1/validate')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(ActorCodes::class);

        $actorCode = $sut->validateCode('valid-code', 'M-1234-1234-1234', '1959-08-10');

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(ActorCodeIsValid::class, $actorCode->getData());
        self::assertEquals('74673c83-05ba-4886-beb1-daaa36fb7984', $actorCode->getData()->actorUid);
        self::assertTrue($actorCode->getData()->hasPaperVerificationCode);
    }

    #[Test]
    public function it_marks_a_code_as_used(): void
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/revoke')
            ->setHeaders(
                [
                    'Accept'                          => 'application/vnd.opg-data.v1+json,application/json',
                    'Authorization'                   => $matcher->like('AWS4-HMAC-SHA256'),
                    'Content-Type'                    => 'application/json',
                    RequestTracing::TRACE_HEADER_NAME => $matcher->like('trace-id'),
                ]
            )
            ->setBody(
                [
                    'code' => $matcher->regex('ISAVALIDCODE', '[A-Z0-9]{12}'),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->setBody(
                [
                    'codes revoked' => 1,
                ]
            );

        $this->builder
            ->given('the activation key ISAVALIDCODE exists')
            ->uponReceiving('a request to revoke the code ISAVALIDCODE')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(ActorCodes::class);

        $sut->flagCodeAsUsed('ISAVALIDCODE');

        self::assertTrue($this->builder->verify());
    }
}
