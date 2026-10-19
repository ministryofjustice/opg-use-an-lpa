<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Entity\User;
use Common\Service\SystemMessage\SystemMessageService;
use Common\View\Twig\RequestGlobalsExtension;
use Common\View\Twig\RequestGlobalsRuntime;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

#[CoversClass(RequestGlobalsExtension::class)]
#[CoversClass(RequestGlobalsRuntime::class)]
class IntegrationTest extends IntegrationTestCase
{
    protected RequestGlobalsRuntime $runtime;
    protected SystemMessageService $systemMessageService;

    public function getExtensions(): array
    {
        return [
            new RequestGlobalsExtension(),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/Fixtures/';
    }

    protected function getRuntimeLoaders(): array
    {
        $this->systemMessageService = $this->createStub(SystemMessageService::class);
        $this->systemMessageService->method('getMessages')->willReturn([
            'use/en' => '<English message>',
            'use/cy' => 'Welsh message',
        ]);

        $this->runtime = new RequestGlobalsRuntime($this->systemMessageService);
        $this->runtime->setUser(new User('account-id', [], ['Email' => 'user@example.com']));

        $flash = $this->createStub(FlashMessagesInterface::class);
        $flash->method('getFlash')->willReturn('<LPA added>');
        $this->runtime->setFlashMessages($flash);

        return [
            new FactoryRuntimeLoader([
                RequestGlobalsRuntime::class => fn (): RequestGlobalsRuntime => $this->runtime,
            ]),
        ];
    }

    // The Twig helper uses annotation-based providers, which PHPUnit 12 no longer supports
    #[DataProvider('integrationFixtures')]
    public function testIntegration(
        $file,
        $message,
        $condition,
        $templates,
        $exception,
        $outputs,
        $deprecation = '',
    ): void {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    // Wrap the data provider from the overridden class
    public static function integrationFixtures(): iterable
    {
        return (new self('testIntegration'))->getTests('testIntegration');
    }

    #[DataProvider('legacyIntegrationFixtures')]
    public function testLegacyIntegration(
        $file,
        $message,
        $condition,
        $templates,
        $exception,
        $outputs,
        $deprecation = '',
    ): void {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    public static function legacyIntegrationFixtures(): iterable
    {
        return (new self('testLegacyIntegration'))->getTests('testLegacyIntegration', true);
    }
}
