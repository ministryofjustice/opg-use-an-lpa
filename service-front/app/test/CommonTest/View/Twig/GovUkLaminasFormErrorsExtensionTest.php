<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\GovUKLaminasFormErrorsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class GovUkLaminasFormErrorsExtensionTest extends TestCase
{
    /** @test */
    public function it_returns_an_array_of_exported_twig_functions()
    {
        $extension = new GovUKLaminasFormErrorsExtension();

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));

        $expectedFunctions = [
            'govuk_error'           => 'errorMessage',
            'govuk_error_summary'   => 'errorSummary',
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            /** @var TwigFunction $function */
            $this->assertContains($function->getName(), array_keys($expectedFunctions));

            $functionCallable = $function->getCallable();
            $this->assertInstanceOf(GovUKLaminasFormErrorsExtension::class, $functionCallable[0]);
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable[1]);
        }
    }


}
