<?php

declare(strict_types=1);

namespace FunctionalTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\Repository\Response\PaperVerificationCode as PaperVerificationCodeResponse;
use App\DataAccess\Repository\Response\PaperVerificationCodeExpiry;
use App\Enum\VerificationCodeExpiryReason;
use App\Exception\NotFoundException;
use App\Service\Log\RequestTracing;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use DateInterval;
use DateTimeImmutable;
use FunctionalTest\AbstractFunctionalTestCase;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerConfig;
use PHPUnit\Framework\Attributes\Test;

class PaperVerificationCodesTest extends AbstractFunctionalTestCase
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
    public function it_validates_a_code_that_is_valid_and_unused(): void
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/paper-verification-code/validate')
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
                    'code' => $matcher->regex(
                        'P-1234-1234-1234-12',
                        'P(-[A-Z0-9]{4}){3}-[A-Z0-9]{2}'
                    ),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody(
                [
                    'lpa'   => $matcher->regex('M-7890-0400-4000', 'M(-[0-9]{4}){3}'),
                    'actor' => $matcher->uuid(),
                ]
            );

        $this->builder
            ->given('the paper verification code P-1234-1234-1234-12 is valid and unused')
            ->uponReceiving('a request to validate the code P-1234-1234-1234-12')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(PaperVerificationCodes::class);

        $pvc = $sut->validate(new PaperVerificationCode('P-1234-1234-1234-12'));

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(PaperVerificationCodeResponse::class, $pvc->getData());
        self::assertEquals('M-7890-0400-4000', (string) $pvc->getData()->lpaUid);
    }

    #[Test]
    public function it_validates_a_code_that_is_valid_and_used(): void
    {
        $expiryDate = (new DateTimeImmutable('now'))
            ->add(new DateInterval('P1Y'))
            ->format('Y-m-d');

        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/paper-verification-code/validate')
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
                    'code' => $matcher->regex(
                        'P-5678-5678-5678-56',
                        'P(-[A-Z0-9]{4}){3}-[A-Z0-9]{2}'
                    ),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody(
                [
                    'lpa'           => $matcher->regex('M-7890-0400-4000', 'M(-[0-9]{4}){3}'),
                    'actor'         => $matcher->uuid(),
                    'expiry_date'   => $matcher->dateISO8601($expiryDate),
                    'expiry_reason' => 'first_time_use',
                ]
            );

        $this->builder
            ->given('the paper verification code P-5678-5678-5678-56 is valid and was used 1 year ago')
            ->uponReceiving('a request to validate the code P-5678-5678-5678-56')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(PaperVerificationCodes::class);

        $pvc = $sut->validate(new PaperVerificationCode('P-5678-5678-5678-56'));

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(PaperVerificationCodeResponse::class, $pvc->getData());
        self::assertEquals('M-7890-0400-4000', (string) $pvc->getData()->lpaUid);
        self::assertEquals($expiryDate, (string) $pvc->getData()->expiresAt->format('Y-m-d'));
        self::assertEquals('first_time_use', $pvc->getData()->expiryReason->value);
    }

    #[Test]
    public function it_does_not_validate_a_nonexistent_code(): void
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/paper-verification-code/validate')
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
                    'code' => $matcher->regex(
                        'P-6789-6789-6789-67',
                        'P(-[A-Z0-9]{4}){3}-[A-Z0-9]{2}'
                    ),
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(404)
            ->addHeader('Content-Type', 'application/vnd.opg-data.v1+json')
            ->setBody(
                [
                    'errors' => $matcher->eachLike(
                        [
                            'code'  => 'OPGDATA-API-NOTFOUND',
                            'title' => 'Code not found',
                        ]
                    ),
                ]
            );

        $this->builder
            ->given('the paper verification code P-6789-6789-6789-67 does not exist')
            ->uponReceiving('a request to validate the code P-6789-6789-6789-67')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(PaperVerificationCodes::class);

        try {
            $sut->validate(new PaperVerificationCode('P-6789-6789-6789-67'));
        } catch (NotFoundException) {
            self::assertTrue($this->builder->verify());
            return;
        }

        self::fail('Expected NotFoundException was not thrown');
    }

    #[Test]
    public function it_expires_a_provided_code_with_a_reason(): void
    {
        $expiryDate = (new DateTimeImmutable('now'))
            ->add(new DateInterval('P1Y'))
            ->format('Y-m-d');

        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/paper-verification-code/expire')
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
                    'code'          => $matcher->regex(
                        'P-1234-1234-1234-12',
                        'P(-[A-Z0-9]{4}){3}-[A-Z0-9]{2}'
                    ),
                    'expiry_reason' => VerificationCodeExpiryReason::FIRST_TIME_USE,
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody(
                [
                    'expiry_date' => $matcher->dateISO8601($expiryDate),
                ]
            );

        $this->builder
            ->given('the paper verification code P-1234-1234-1234-12 has not got an expiry date')
            ->uponReceiving('a request to expire the code P-1234-1234-1234-12 as a first_time_use')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(PaperVerificationCodes::class);

        $pvc = $sut->expire(
            new PaperVerificationCode('P-1234-1234-1234-12'),
            VerificationCodeExpiryReason::FIRST_TIME_USE
        );

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(PaperVerificationCodeExpiry::class, $pvc->getData());
        self::assertEquals($expiryDate, (string) $pvc->getData()->expiresAt->format('Y-m-d'));
    }

    #[Test]
    public function it_transitions_a_code_to_online_usage(): void
    {
        $expiryDate = (new DateTimeImmutable('now'))
            ->add(new DateInterval('P30D'))
            ->format('Y-m-d');

        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v1/paper-verification-code/expire')
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
                    'lpa'           => $matcher->regex('M-7890-0400-4000', 'M(-[0-9]{4}){3}'),
                    'actor'         => $matcher->uuid(),
                    'expiry_reason' => VerificationCodeExpiryReason::PAPER_TO_DIGITAL,
                ]
            );

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody(
                [
                    'expiry_date' => $matcher->dateISO8601($expiryDate),
                ]
            );

        $this->builder
            ->given('the paper verification code P-1234-1234-1234-12 has not got an expiry date')
            ->uponReceiving('a request to expire the code P-1234-1234-1234-12 as a paper_to_digital')
            ->with($request)
            ->willRespondWith($response);

        $sut = $this->container->get(PaperVerificationCodes::class);

        $pvc = $sut->transitionToDigital(
            new LpaUid('M-7890-0400-4000'),
            '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d'
        );

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(PaperVerificationCodeExpiry::class, $pvc->getData());
        self::assertEquals($expiryDate, (string) $pvc->getData()->expiresAt->format('Y-m-d'));
    }
}
