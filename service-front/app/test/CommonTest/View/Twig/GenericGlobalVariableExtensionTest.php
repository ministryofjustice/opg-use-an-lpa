<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use PHPUnit\Framework\Attributes\Test;
use Acpr\I18n\TranslatorInterface;
use Common\View\Twig\GenericGlobalVariableExtension;
use PHPUnit\Framework\TestCase;

class GenericGlobalVariableExtensionTest extends TestCase
{
    #[Test]
    public function sets_some_global_variables(): void
    {
        $application = 'actor';

        $translatorInterface = $this->createMock(TranslatorInterface::class);
        $translatorInterface->method('translate')->willReturnArgument(0);

        $extension     = new GenericGlobalVariableExtension($application, $translatorInterface);
        $genericConfig = $extension->getGlobals();

        $this->assertEquals(3, count($genericConfig));

        $expectedConfig = [
            'application'   => 'actor',
            'serviceName'   => 'Use a lasting power of attorney',
            'currentLocale' => 'cy-gb',
        ];

        $this->assertEquals($expectedConfig, $genericConfig);
        $this->assertEquals($expectedConfig['application'], $genericConfig['application']);
        $this->assertEquals($expectedConfig['serviceName'], $genericConfig['serviceName']);
        $this->assertEquals($expectedConfig['currentLocale'], $genericConfig['currentLocale']);
    }

    #[Test]
    public function sets_the_right_app_name(): void
    {
        $application = 'viewer';

        $translatorInterface = $this->createMock(TranslatorInterface::class);
        $translatorInterface->method('translate')->willReturnArgument(0);

        $extension     = new GenericGlobalVariableExtension($application, $translatorInterface);
        $genericConfig = $extension->getGlobals();

        $this->assertEquals('View a lasting power of attorney', $genericConfig['serviceName']);
    }
}
