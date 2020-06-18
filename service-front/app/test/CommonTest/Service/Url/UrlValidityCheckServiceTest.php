<?php

declare(strict_types=1);

namespace CommonTest\Service\Url;

use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlValidityCheckServiceTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    private $serverRequestFactoryProphecy;

    /**
     * @var RouterInterface
     */
    protected $routerProphecy;

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestInterfaceProphecy;

    public function setUp()
    {
        $this->serverRequestFactoryProphecy = $this->prophesize(ServerRequestFactory::class);
        $this->routerProphecy = $this->prophesize(RouterInterface::class);
        $this->serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
    }

    /** @test */
    public function it_checks_if_referer_url_is_valid()
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/add-details';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal()
        );

        $valid = $service->isValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertTrue($valid);
    }

    /** @test */
    public function it_checks_if_referer_url_is_invalid()
    {
        $refererUrl = 'https:///wwww.your_web_app.com/script.php?info_variable=123xyz';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal()
        );

        $valid = $service->isValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertFalse($valid);
    }

    /** @test */
    public function it_checks_if_referer_route_exists()
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/add-details';

        $routeResult = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            "method",
            'php://temp'
        );

        /** @var ServerRequestFactory&ObjectProphecy $serverRequestFactory */
        $serverRequestFactory = $this->prophesize(ServerRequestFactory::class);
        $serverRequestFactory->createServerRequest('GET', $refererUrl)->willReturn($requestReturn);

        /** @var RouterInterface&ObjectProphecy $router */
        $router = $this->prophesize(RouterInterface::class);
        $router->match($requestReturn)->willReturn($routeResult->reveal());

        $service = new UrlValidityCheckService(
            $serverRequestFactory->reveal(),
            $router->reveal()
        );

        $valid = $service->checkRefererRouteValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertTrue($valid);
    }
}
