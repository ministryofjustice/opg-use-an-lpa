<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\OneLogin\OneLoginService;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class OneLoginServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function can_get_authentication_request_uri(): void
    {
        $state = 'STATE';
        $nonce = 'aEwkamaos5B';
        $uri   = '/authorize?response_type=code
            &scope=YOUR_SCOPES
            &client_id=YOUR_CLIENT_ID
            &state=' . $state .
            '&redirect_uri=YOUR_REDIRECT_URI
            &nonce=' . $nonce .
            '&vtr=["Cl.Cm"]
            &ui_locales=en';

        $apiClientProphecy = $this->prophesize(ApiClient::class);

        $apiClientProphecy
            ->httpGet(
                '/v1/auth-one-login',
                [
                    'ui_locale' => 'en',
                ]
            )->willReturn(['state' => $state, 'nonce' => $nonce, 'url' => $uri]);

        $oneLoginService = new OneLoginService($apiClientProphecy->reveal());
        $response        = $oneLoginService->authenticate('en');
        $this->assertEquals(['state' => $state, 'nonce' => $nonce, 'url' => $uri], $response);
    }
}
