<?php

declare(strict_types=1);

namespace CommonTest\Service\Url;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlValidityCheckServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|ServerRequestInterface $serverRequestFactoryProphecy;
    private ObjectProphecy|RouterInterface $routerProphecy;
    private ObjectProphecy|ServerRequestFactoryInterface $serverRequestInterfaceProphecy;
    private ObjectProphecy|UrlHelper $urlHelperProphecy;
    private string $locale;

    public function setUp(): void
    {
        $this->serverRequestFactoryProphecy   = $this->prophesize(ServerRequestFactory::class);
        $this->routerProphecy                 = $this->prophesize(RouterInterface::class);
        $this->serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->urlHelperProphecy              = $this->prophesize(UrlHelper::class);
        $this->locale                         = 'en_GB';
    }

    #[Test]
    public function it_checks_if_referer_url_is_valid(): void
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/add-details';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $valid = $service->isValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertTrue($valid);
    }

    #[Test]
    public function it_checks_if_referer_url_is_invalid(): void
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

    #[Test]
    public function it_checks_if_referer_route_exists(): void
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/add-details';

        $routeResult   = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            'method',
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

        $valid = $service->checkReferrerRouteValid($refererUrl);

        $this->assertIsBool($valid);
        $this->assertTrue($valid);
    }

    #[Test]
    public function it_returns_a_valid_referer(): void
    {
        $refererUrl = 'https://366uml695cook.use.lastingpowerofattorney.opg.service.justice.gov.uk/lpa/dashboard';

        $routeResult   = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            'method',
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

        $resultReferer = $service->setValidReferrer($refererUrl);
        $this->assertEquals($refererUrl, $resultReferer);
    }

    #[Test]
    public function it_returns_a_url_for_home_if_referer_is_invalid(): void
    {
        $homeUrl    = 'https://localhost:9002/';
        $refererUrl = 'https://www.invalid/url';

        $routeResult   = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $refererUrl,
            'method',
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

        $resultReferer = $service->setValidReferrer($refererUrl);

        $this->assertEquals($homeUrl, $resultReferer);
    }

    #[Test]
    public function it_returns_a_url_for_home_if_referer_is_null(): void
    {
        $homeUrl = 'https://localhost:9002/';

        $service = new UrlValidityCheckService(
            $this->serverRequestFactoryProphecy->reveal(),
            $this->routerProphecy->reveal(),
            $this->urlHelperProphecy->reveal()
        );

        $this->urlHelperProphecy->generate('home')->willReturn($homeUrl);

        $resultReferer = $service->setValidReferrer(null);

        $this->assertEquals($homeUrl, $resultReferer);
    }

    #[Test]
    public function it_returns_a_welsh_url_for_home_if_referer_is_invalid_and_locale_is_cy(): void
    {
        $this->locale      = 'cy_GB';
        $englishRefererUrl = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/login';
        $homeUrl           = 'https://localhost:9002/home';
        $welshHomeUrl      = 'https://localhost:9002/cy/home';

        $routeResult   = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $englishRefererUrl,
            'method',
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

        $resultReferer = $service->setValidReferrer($englishRefererUrl);

        $this->assertEquals($welshHomeUrl, $resultReferer);
    }

    #[Test]
    public function it_returns_a_valid_welsh_referer_if_locale_is_cy(): void
    {
        $this->locale      = 'cy_GB';
        $refererUrl        = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/cy/login';
        $englishRefererUrl = 'https://use.lastingpowerofattorney.opg.service.justice.gov.uk/login';

        $routeResult   = $this->prophesize(RouteResult::class);
        $requestReturn =  new ServerRequest(
            [],
            [],
            $englishRefererUrl,
            'method',
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

        $resultReferer = $service->setValidReferrer($refererUrl);
        $this->assertEquals($refererUrl, $resultReferer);
    }
}
