<?php

declare(strict_types=1);

namespace FunctionalTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\Repository\Response\PaperVerificationCode as PaperVerificationCodeResponse;
use App\Exception\NotFoundException;
use App\Service\Log\RequestTracing;
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
        $mockServer->setPactDir('/tmp/pacts');
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
                    'lpa'   => $matcher->regex('M-7890-0400-4003', 'M(-[0-9]{4}){3}'),
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
        self::assertEquals('M-7890-0400-4003', (string) $pvc->getData()->lpaUid);
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
                    'lpa'           => $matcher->regex('M-7890-0400-4003', 'M(-[0-9]{4}){3}'),
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

        $pvc = $sut->validate(new PaperVerificationCode('P-1234-1234-1234-12'));

        self::assertTrue($this->builder->verify());
        self::assertInstanceOf(PaperVerificationCodeResponse::class, $pvc->getData());
        self::assertEquals('M-7890-0400-4003', (string) $pvc->getData()->lpaUid);
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
                            'id'     => $matcher->like('A123BCD'),
                            'code'   => 'OPGDATA-API-NOTFOUND',
                            'title'  => 'Page not found',
                            'detail' => 'That URL is not a valid route, or the item resource does not exist',
                            'meta'   => $matcher->eachLike(
                                [
                                    'x-ray' => $matcher->like('93c330d4-7d84-4c1b-8fdb-54cec5bfe747'),
                                ]
                            ),
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
}
