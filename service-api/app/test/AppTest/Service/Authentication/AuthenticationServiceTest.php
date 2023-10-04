<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\AuthenticationService;
use App\Service\Authentication\JWKFactory;
use App\Service\Authentication\KeyPairManager;
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
        $this->keyPairManager = $this->prophesize(KeyPairManager::class);
        $this->JWKFactory = new JWKFactory($this->keyPairManager->reveal());
        $this->logger     = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @test
     */
    public function getRedirectUri(): void
    {
        $authenticationService = new AuthenticationService($this->JWKFactory, $this->logger->reveal());
        $redirectUri           = $authenticationService->redirect('en');
        $this->assertStringContainsString('client_id=client-id', $redirectUri);
        $this->assertStringContainsString('scope=openid+email', $redirectUri);
        $this->assertStringContainsString('vtr=%5B%22Cl.Cm.P2%22%5D', $redirectUri);
    }
}
