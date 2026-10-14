<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Entity\User;
use Common\Service\SystemMessage\SystemMessageService;
use Common\View\Twig\RequestGlobalsExtension;
use Common\View\Twig\RequestGlobalsRuntime;
use DI\Container;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

#[CoversClass(RequestGlobalsExtension::class)]
final class RequestGlobalsExtensionTest extends TestCase
{
    #[Test]
    public function it_should_load_the_runtime_only_when_a_function_is_called(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::never())->method('getMessages');
        $runtime   = new RequestGlobalsRuntime($service);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('has')->with(RequestGlobalsRuntime::class)->willReturn(true);
        $container->expects(self::once())->method('get')->with(RequestGlobalsRuntime::class)->willReturn($runtime);
        $twig = new Environment(new ArrayLoader([
            'plain' => 'No request functions',
            'user'  => '{{ user() is null ? "anonymous" : "signed in" }}',
        ]));
        $twig->addRuntimeLoader(new ContainerRuntimeLoader($container));
        $twig->addExtension(new RequestGlobalsExtension());

        self::assertSame('No request functions', $twig->render('plain'));
        self::assertSame('anonymous', $twig->render('user'));
    }

    #[Test]
    public function it_should_render_the_user_function_in_inherited_and_included_templates(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::never())->method('getMessages');
        $runtime = new RequestGlobalsRuntime($service);
        $runtime->setUser(new User('account-id', [], ['Email' => 'user@example.com']));
        $loader = new ContainerRuntimeLoader(new Container([RequestGlobalsRuntime::class => $runtime]));
        $twig   = new Environment(new ArrayLoader([
            'page'   => '{% extends "layout" %}{% block content %}{{ user().identity }}{% endblock %}',
            'layout' => '{% include "header" %}:{% block content %}{% endblock %}',
            'header' => '{{ user().getDetail("Email") }}',
        ]), ['strict_variables' => true]);
        $twig->addRuntimeLoader($loader);
        $twig->addExtension(new RequestGlobalsExtension());

        self::assertSame($runtime, $twig->getRuntime(RequestGlobalsRuntime::class));
        self::assertSame('user@example.com:account-id', $twig->render('page'));

        $override = new User('override-id', [], ['Email' => 'override@example.com']);
        self::assertSame('user@example.com:account-id', $twig->render('page', ['user' => $override]));
    }

    #[Test]
    public function it_should_render_the_flash_function_without_fetching_system_messages(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::never())->method('getMessages');
        $runtime = new RequestGlobalsRuntime($service);
        $flash   = $this->createMock(FlashMessagesInterface::class);
        $flash->expects(self::once())->method('getFlash')->with('added')->willReturn('<LPA added>');
        $runtime->setFlashMessages($flash);
        $twig = new Environment(new ArrayLoader(['page' => '{{ flash().getFlash("added") }}']));
        $twig->addRuntimeLoader(new ContainerRuntimeLoader(new Container([RequestGlobalsRuntime::class => $runtime])));
        $twig->addExtension(new RequestGlobalsExtension());

        self::assertSame('&lt;LPA added&gt;', $twig->render('page'));
    }

    #[Test]
    public function it_should_render_system_messages_lazily_from_the_function(): void
    {
        $service = $this->createMock(SystemMessageService::class);
        $service->expects(self::once())->method('getMessages')->willReturn([
            'use/en' => '<English message>',
            'use/cy' => 'Welsh message',
        ]);
        $runtime = new RequestGlobalsRuntime($service);
        $loader  = new ContainerRuntimeLoader(new Container([RequestGlobalsRuntime::class => $runtime]));
        $twig    = new Environment(new ArrayLoader([
            'page'   => '{% set messages = system_messages() %}{% include "banner" with {
                en_message: messages["use/en"]|default(null),
                cy_message: messages["use/cy"]|default(null)
            } %}',
            'banner' => '{{ en_message }}:{{ cy_message }}',
        ]), ['strict_variables' => true]);
        $twig->addRuntimeLoader($loader);
        $twig->addExtension(new RequestGlobalsExtension());

        self::assertSame('&lt;English message&gt;:Welsh message', $twig->render('page'));
        self::assertSame('&lt;English message&gt;:Welsh message', $twig->render('page'));
    }
}
