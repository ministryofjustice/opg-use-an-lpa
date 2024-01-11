<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use Closure;
use Common\Entity\User;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\OneLogin\OneLoginService;
use DateTime;
use DateTimeInterface;
use Facile\OpenIDClient\Session\AuthSession;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class OneLoginServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|LoggerInterface $logger;
    private Closure $userFactoryCallable;

    public function setUp() : void{
        $this->logger              = $this->prophesize(LoggerInterface::class);
        $this->userFactoryCallable = function ($identity, $roles, $details) {
            $this->assertEquals('fake-id', $identity);
            $this->assertIsArray($roles);
            $this->assertIsArray($details);
            $this->assertArrayHasKey('Email', $details);
            $this->assertArrayHasKey('LastLogin', $details);
            $this->assertArrayHasKey('Subject', $details);

            return new User($identity, $roles, $details);
        };
    }

    /** @test */
    public function can_get_authentication_request_uri(): void
    {
        $state    = 'STATE';
        $nonce    = 'aEwkamaos5B';
        $redirect = 'FAKE_REDIRECT';
        $uri      = '/authorize?response_type=code
            &scope=YOUR_SCOPES
            &client_id=YOUR_CLIENT_ID
            &state=' . $state .
            '&redirect_uri=' . $redirect .
            '&nonce=' . $nonce .
            '&vtr=["Cl.Cm"]
            &ui_locales=en';

        $apiClientProphecy = $this->prophesize(ApiClient::class);

        $apiClientProphecy
            ->httpGet(
                '/v1/auth/start',
                [
                    'ui_locale'    => 'en',
                    'redirect_url' => $redirect,
                ]
            )->willReturn(['state' => $state, 'nonce' => $nonce, 'url' => $uri]);

        $oneLoginService = new OneLoginService(
            $apiClientProphecy->reveal(),
            $this->userFactoryCallable,
            $this->logger->reveal()
        );
        $response        = $oneLoginService->authenticate('en', $redirect);
        $this->assertEquals(['state' => $state, 'nonce' => $nonce, 'url' => $uri], $response);
    }

    /** @test */
    public function can_get_callback_request_uri(): void
    {
        $state           = 'fakeState';
        $code            = 'fakeCode';
        $nonce           = 'fakeNonce';
        $redirect        = 'FAKE_REDIRECT';
        $authCredentials = AuthSession::fromArray([
            'state'   => $state,
            'nonce'   => $nonce,
            'customs' => [
                 'ui_locale'    => 'en',
                 'redirect_uri' => $redirect,
            ],
        ]);

        $apiClientProphecy = $this->prophesize(ApiClient::class);

        $lastLogin = (new DateTime('-1 day'))->format(DateTimeInterface::ATOM);

        $apiClientProphecy
            ->httpPost(
                '/v1/auth/callback',
                [
                    'code'         => $code,
                    'state'        => $state,
                    'auth_session' => $authCredentials,
                ]
            )->willReturn([
                'Id'         => 'fake-id',
                'Identity'   => 'fake-sub-identity',
                'Email'      => 'fake@email.com',
                'LastLogin'  => $lastLogin,
                'Birthday'   => '1990-01-01',
                'NeedsReset' => false,
            ]);

        $oneLoginService = new OneLoginService(
            $apiClientProphecy->reveal(),
            $this->userFactoryCallable,
            $this->logger->reveal()
        );
        $response        = $oneLoginService->callback($code, $state, $authCredentials);

        $this->assertInstanceOf(User::class, $response);
        $this->assertEquals('fake-id', $response->getIdentity());
        $this->assertEquals(new DateTime($lastLogin), $response->getDetail('lastLogin'));
        $this->assertEquals('fake@email.com', $response->getDetail('email'));
        $this->assertEquals('fake-sub-identity', $response->getDetail('subject'));
        $this->assertEquals(false, $response->getDetail('NeedsReset'));
    }
}
