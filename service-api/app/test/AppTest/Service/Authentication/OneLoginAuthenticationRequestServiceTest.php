<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\AuthorisationService;
use App\Service\Authentication\AuthorisationServiceBuilder;
use App\Service\Authentication\OneLoginService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class OneLoginAuthenticationRequestServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function create_authentication_request(): void
    {
        $fakeRedirect = 'http://fakehost/auth/redirect';

        $service = $this->prophesize(AuthorisationService::class);
        $service->getAuthorisationUri(Argument::that(function (array $configuration) use ($fakeRedirect): bool {
            $this->assertArrayHasKey('state', $configuration);
            $this->assertArrayHasKey('nonce', $configuration);

            // these are random values so remove them before compare operation.
            unset($configuration['state']);
            unset($configuration['nonce']);

            $this->assertEquals(
                [
                    'scope'        => 'openid email',
                    'redirect_uri' => $fakeRedirect,
                    'vtr'          => '["Cl.Cm.P2"]',
                    'ui_locales'   => 'en',
                    'claims'       => '{"userinfo":{"https://vocab.account.gov.uk/v1/coreIdentityJWT": null}}',
                ],
                $configuration,
            );

            return true;
        }))->willReturn($fakeRedirect . '?with_suitable_values=true');

        $serviceBuilder = $this->prophesize(AuthorisationServiceBuilder::class);
        $serviceBuilder->build()
            ->willReturn($service->reveal());

        $authorisationRequestService = new OneLoginService($serviceBuilder->reveal());

        $authorisationRequest = $authorisationRequestService->createAuthenticationRequest('en', $fakeRedirect);
    }
}
