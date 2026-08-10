<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\CSPMiddleware;
use Common\Service\Security\CSPNonce;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CSPMiddleware::class)]
class CSPMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    #[DataProvider('cspTestCases')]
    public function it_correctly_attaches_a_csp_header(
        string $routeName,
        string $application,
        bool $enforce,
        string $headerName,
        string $authenticationRegex,
        string $iapRegex,
    ): void {
        $testNonce = new CSPNonce('test');

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult
            ->getMatchedRouteName()
            ->willReturn($routeName);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->withAttribute(CSPMiddleware::NONCE_ATTRIBUTE, $testNonce)
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());
        $requestProphecy
            ->getAttribute(RouteResult::class)
            ->shouldBeCalled()
            ->willReturn($routeResult->reveal());

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy
            ->withAddedHeader(Argument::cetera())
            ->will(function (array $arguments) use ($responseProphecy, $headerName, $authenticationRegex, $iapRegex) {
                Assert::assertEquals($headerName, $arguments[0]);

                Assert::assertMatchesRegularExpression('`;report-uri https://report_uri/_csp;`', $arguments[1]);

                // Nonce should be applied
                Assert::assertMatchesRegularExpression('`script-src.*?(nonce-test).*?;`', $arguments[1]);
                Assert::assertMatchesRegularExpression('`style-src.*?(nonce-test).*?;`', $arguments[1]);

                Assert::assertMatchesRegularExpression($authenticationRegex, $arguments[1]);
                Assert::assertMatchesRegularExpression($iapRegex, $arguments[1]);

                return $responseProphecy->reveal();
            });

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($responseProphecy->reveal());

        $sut = new CSPMiddleware(
            $enforce,
            'https://report_uri',
            $testNonce,
            $application,
            'https://*.authentication_domain',
            'https://images_domain',
        );

        $sut->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    public static function cspTestCases(): array
    {
        return [
            'attaches authentication domain to actor homepage form-action'        => [
                'home', // route name
                'actor', // application
                true, // enforce
                'Content-Security-Policy', // header name expectation
                '`img-src((?!https://images_domain).)*?;`', // image (should match)
                '`form-action.*?(https://\*\.authentication_domain).*?;`', // authentication (should match)
            ],
            "doesn't attach authentication domain to viewer homepage form-action" => [
                'home',
                'viewer',
                true,
                'Content-Security-Policy',
                '`img-src((?!https://images_domain).)*?;`',
                '`form-action((?!https://\*\.authentication_domain).)*?;`',
            ],
            'attaches image domain on viewer lpa view page'                       => [
                'view-lpa',
                'viewer',
                true,
                'Content-Security-Policy',
                '`img-src.*?(https://images_domain).*?;`',
                '`form-action((?!https://\*\.authentication_domain).)*?;`',
            ],
            'attaches image domain on actor lpa summary page'                     => [
                'lpa.view',
                'actor',
                true,
                'Content-Security-Policy',
                '`img-src.*?(https://images_domain).*?;`',
                '`form-action((?!https://\*\.authentication_domain).)*?;`',
            ],
            'no domains attached when not needed by page'                         => [
                'lpa.dashboard',
                'actor',
                true,
                'Content-Security-Policy',
                '`img-src((?!https://images_domain).)*?;`',
                '`form-action((?!https://\*\.authentication_domain).)*?;`',
            ],
            'report only when enforcement off'                                    => [
                'lpa.dashboard',
                'actor',
                false,
                'Content-Security-Policy-Report-Only',
                '`img-src((?!https://images_domain).)*?;`',
                '`form-action((?!https://\*\.authentication_domain).)*?;`',
            ],
        ];
    }
}
