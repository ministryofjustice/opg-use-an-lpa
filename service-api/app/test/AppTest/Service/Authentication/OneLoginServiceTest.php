<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Exception\AuthorisationServiceException;
use App\Service\Authentication\AuthorisationService;
use App\Service\Authentication\AuthorisationServiceBuilder;
use App\Service\Authentication\OneLoginService;
use App\Service\Authentication\UserInfoService;
use Facile\OpenIDClient\Token\TokenSetInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class OneLoginServiceTest extends TestCase
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
                    'claims'       => '{"userinfo":{"https://vocab.account.gov.uk/v1/coreIdentityJWT":null}}',
                ],
                $configuration,
            );

            return true;
        }))->willReturn($fakeRedirect . '?with_suitable_values=true');

        $userInfoService = $this->prophesize(UserInfoService::class);

        $serviceBuilder = $this->prophesize(AuthorisationServiceBuilder::class);
        $serviceBuilder->build()
            ->willReturn($service->reveal());

        $authorisationRequestService = new OneLoginService($serviceBuilder->reveal(), $userInfoService->reveal());

        $authorisationRequest = $authorisationRequestService->createAuthenticationRequest('en', $fakeRedirect);
    }

    /**
     * @test
     */
    public function handle_callback(): void
    {
        $fakeRedirect = 'http://fakehost/auth/redirect';
        $fakeSession  = [
            'state'   => 'fake_state',
            'nonce'   => 'fake_nonce',
            'customs' => [
                'redirect_uri' => $fakeRedirect,
            ],
        ];

        $tokenSet = $this->prophesize(TokenSetInterface::class);
        $tokenSet->getIdToken()->willReturn('fakeToken');

        $service = $this->prophesize(AuthorisationService::class);
        $service
            ->callback('fake_code', 'fake_state', $fakeSession)
            ->willReturn($tokenSet->reveal());

        $userInfoService = $this->prophesize(UserInfoService::class);
        $userInfoService
            ->getUserInfo($tokenSet->reveal())
            ->willReturn(
                [
                    'sub'                                             => 'fakeSub',
                    'email'                                           => 'fakeEmail',
                    'https://vocab.account.gov.uk/v1/coreIdentityJWT' => 'fakeJWT',
                ]
            );
        $userInfoService
            ->processCoreIdentity('fakeJWT')
            ->willReturn(
                [
                    'birthDate' => [
                        ['value' => '1982-10-82'],
                    ],
                ],
            );

        $serviceBuilder = $this->prophesize(AuthorisationServiceBuilder::class);
        $serviceBuilder->build()
            ->willReturn($service->reveal());

        $sut = new OneLoginService($serviceBuilder->reveal(), $userInfoService->reveal());

        $user = $sut->handleCallback(
            'fake_code',
            'fake_state',
            $fakeSession,
        );

        $this->assertArrayHasKey('Id', $user);
        $this->assertArrayHasKey('Identity', $user);
        $this->assertArrayHasKey('Email', $user);
        $this->assertArrayHasKey('Birthday', $user);

        $this->assertSame('fakeSub', $user['Identity']);
        $this->assertSame('fakeEmail', $user['Email']);
        $this->assertSame('1982-10-82', $user['Birthday']);
    }

    /**
     * @test
     */
    public function handle_callback_missing_token(): void
    {
        $fakeRedirect = 'http://fakehost/auth/redirect';
        $fakeSession  = [
            'state'   => 'fake_state',
            'nonce'   => 'fake_nonce',
            'customs' => [
                'redirect_uri' => $fakeRedirect,
            ],
        ];

        $tokenSet = $this->prophesize(TokenSetInterface::class);

        $service = $this->prophesize(AuthorisationService::class);
        $service
            ->callback('fake_code', 'fake_state', $fakeSession)
            ->willReturn($tokenSet->reveal());

        $userInfoService = $this->prophesize(UserInfoService::class);

        $serviceBuilder = $this->prophesize(AuthorisationServiceBuilder::class);
        $serviceBuilder->build()
            ->willReturn($service->reveal());

        $sut = new OneLoginService($serviceBuilder->reveal(), $userInfoService->reveal());

        $this->expectException(AuthorisationServiceException::class);
        $user = $sut->handleCallback(
            'fake_code',
            'fake_state',
            $fakeSession,
        );
    }

    /**
     * @test
     */
    public function handle_callback_missing_identity(): void
    {
        $fakeRedirect = 'http://fakehost/auth/redirect';
        $fakeSession  = [
            'state'   => 'fake_state',
            'nonce'   => 'fake_nonce',
            'customs' => [
                'redirect_uri' => $fakeRedirect,
            ],
        ];

        $tokenSet = $this->prophesize(TokenSetInterface::class);
        $tokenSet->getIdToken()->willReturn('fakeToken');

        $service = $this->prophesize(AuthorisationService::class);
        $service
            ->callback('fake_code', 'fake_state', $fakeSession)
            ->willReturn($tokenSet->reveal());

        $userInfoService = $this->prophesize(UserInfoService::class);
        $userInfoService
            ->getUserInfo($tokenSet->reveal())
            ->willReturn(
                [
                    'sub'   => 'fakeSub',
                    'email' => 'fakeEmail',
                ]
            );

        $serviceBuilder = $this->prophesize(AuthorisationServiceBuilder::class);
        $serviceBuilder->build()
            ->willReturn($service->reveal());

        $sut = new OneLoginService($serviceBuilder->reveal(), $userInfoService->reveal());

        $this->expectException(AuthorisationServiceException::class);
        $user = $sut->handleCallback(
            'fake_code',
            'fake_state',
            $fakeSession,
        );
    }
}
