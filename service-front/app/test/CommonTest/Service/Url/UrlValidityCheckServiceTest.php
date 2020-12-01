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
use Mezzio\Helper\UrlHelper;

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

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    private $locale;

    public function setUp()
    {
        $this->serverRequestFactoryProphecy = $this->prophesize(ServerRequestFactory::class);
        $this->routerProphecy = $this->prophesize(RouterInterface::class);
        $this->serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $this->locale = "en_GB";
    }

    /** @test */
    public function it_checks_if_referer_url_is_valid()
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/add/by-code';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
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
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $valid = $service->isValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertFalse($valid);
    }

    /** @test */
    public function it_checks_if_referer_route_exists()
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/add/by-code';

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

        $routeResult->isSuccess()->willReturn(true);

        $service = new UrlValidityCheckService(
            $serverRequestFactory->reveal(),
            $router->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $valid = $service->checkRefererRouteValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertTrue($valid);
    }

    /** @test */
    public function it_returns_a_valid_referer()
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/dashboard';

        $routeResult = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            "method",
            'php://temp'
        );

        $this->serverRequestFactoryProphecy->createServerRequest('GET', $refererUrl)->willReturn($requestReturn);

        $this->routerProphecy->match($requestReturn)->willReturn($routeResult->reveal());

        $routeResult->isSuccess()->willReturn(true);

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $resultReferer = $service->setValidReferer($refererUrl);
        $this->assertEquals($refererUrl, $resultReferer);
    }

    /** @test */
    public function it_returns_a_url_for_home_if_referer_is_invalid()
    {
        $homeUrl = 'https://localhost:9002/';
        $refererUrl = 'https://www.invalid/url';

        $routeResult = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            "method",
            'php://temp'
        );

        $this->serverRequestFactoryProphecy->createServerRequest('GET', $refererUrl)->willReturn($requestReturn);

        $this->routerProphecy->match($requestReturn)->willReturn($routeResult->reveal());

        $routeResult->isSuccess()->willReturn(false);

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $this->urlHelperProphecy->generate('home')->willReturn($homeUrl);

        $resultReferer = $service->setValidReferer($refererUrl);

        $this->assertEquals($homeUrl, $resultReferer);
    }

    /** @test */
    public function it_returns_a_url_for_home_if_referer_is_null()
    {
        $homeUrl = 'https://localhost:9002/';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $this->urlHelperProphecy->generate('home')->willReturn($homeUrl);

        $resultReferer = $service->setValidReferer(null);

        $this->assertEquals($homeUrl, $resultReferer);
    }

    /** @test */
    public function it_returns_a_welsh_url_for_home_if_referer_is_invalid_and_locale_is_cy()
    {
        $this->locale = "cy";
        $englishRefererUrl = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/login';
        $homeUrl = 'https://localhost:9002/home';
        $welshHomeUrl = 'https://localhost:9002/cy/home';

        $routeResult = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $englishRefererUrl,
            "method",
            'php://temp'
        );

        $this->serverRequestFactoryProphecy->createServerRequest('GET', $englishRefererUrl)->willReturn($requestReturn);

        $this->routerProphecy->match($requestReturn)->willReturn($routeResult->reveal());

        $routeResult->isSuccess()->willReturn(false);

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $this->urlHelperProphecy->generate('home')->willReturn($homeUrl);

        $resultReferer = $service->setValidReferer($refererUrl);

        $this->assertEquals($welshHomeUrl, $resultReferer);
    }

    /** @test */
    public function it_returns_a_valid_welsh_referer_if_locale_is_cy()
    {
        $this->locale = "cy";
        $refererUrl = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/cy/login';
        $englishRefererUrl = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/login';

        $routeResult = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $englishRefererUrl,
            "method",
            'php://temp'
        );

        $this->serverRequestFactoryProphecy->createServerRequest('GET', $englishRefererUrl)->willReturn($requestReturn);

        $this->routerProphecy->match($requestReturn)->willReturn($routeResult->reveal());

        $routeResult->isSuccess()->willReturn(true);

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $resultReferer = $service->setValidReferer($refererUrl);
        $this->assertEquals($refererUrl, $resultReferer);
    }
}
