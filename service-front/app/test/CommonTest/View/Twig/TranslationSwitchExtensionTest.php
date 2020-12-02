<?php

namespace CommonTest\View\Twig;

use Common\View\Twig\TranslationSwitchExtension;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class TranslationSwitchExtensionTest extends TestCase
{
    /** @test */
    public function it_returns_an_array_of_exported_twig_functions()
    {
        $urlHelper = $this->prophesize(UrlHelper::class);
        $extension = new TranslationSwitchExtension($urlHelper->reveal());

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));

        $expectedFunctions = [
            'get_route_name'  => 'getRouteName'
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            /** @var TwigFunction $function */
            $this->assertContains($function->getName(), array_keys($expectedFunctions));

            $functionCallable = $function->getCallable();
            $this->assertInstanceOf(TranslationSwitchExtension::class, $functionCallable[0]);
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable[1]);
        }
    }

    /** @test */
    public function it_returns_the_current_route_name()
    {
        $urlHelper = $this->prophesize(UrlHelper::class);
        $routeResult = $this->prophesize(RouteResult::class);

        $switchExtension = new TranslationSwitchExtension($urlHelper->reveal());

        $routeResult
            ->getMatchedRouteName()
            ->willReturn('lpa.add');

        $urlHelper
            ->getRouteResult()
            ->willReturn($routeResult);

        $result = $switchExtension->getRouteName();

        $this->assertEquals('lpa.add', $result);
    }
}
