<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\AuthenticationService;
use App\Service\Authentication\JWKFactory;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AuthenticationServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|JWKFactory $JWKFactory;
    private ObjectProphecy|LoggerInterface $logger;

    public function setup(): void
    {
        $jwk              = $this->prophesize(JWK::class);
        $this->JWKFactory = $this->prophesize(JWKFactory::class);
        $this->JWKFactory->__invoke()->willReturn($jwk);
        $this->logger = $this->prophesize(LoggerInterface::class);

//        $issuer = $this->prophesize(IssuerBuilder::class);
//        $issuer->reveal();
//        $issuer->build('http://mock-one-login:8080/.well-kown/openid-configuration');
    }

    /**
     * @test
     */
    public function getRedirectUri(): void
    {
        $authenticationService = new AuthenticationService($this->JWKFactory->reveal(), $this->logger->reveal());
        // TODO this needs to become an integration test
        $redirectUri = $authenticationService->redirect('en');
        $this->assertStringContainsString('client_id=client-id', $redirectUri);
        $this->assertStringContainsString('scope=openid+email', $redirectUri);
        $this->assertStringContainsString('vtr=%5B%22Cl.Cm.P2%22%5D', $redirectUri);
    }
}
