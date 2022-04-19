<?php

declare(strict_types=1);

namespace ActorTest\Handler;

use Actor\Form\AddLpa\ActivationCode;
use Actor\Handler\AddLpa\ActivationCodeHandler;
use Actor\Workflow\AddLpa;
use Common\Middleware\Workflow\StatePersistenceMiddleware;
use Common\Service\Lpa\LpaService;
use Common\Workflow\StatesCollection;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class LpaAddHandlerTest extends TestCase
{
    const CSRF_CODE = '1234';

    /**
     * @var TemplateRendererInterface
     */
    private $rendererProphecy;

    /**
     * @var UrlHelper
     */
    private $urlHelperProphecy;

    /**
     * @var LpaService
     */
    private $lpaServiceProphecy;

    /**
     * @var AuthenticationInterface
     */
    private $authenticatorProphecy;

    /**
     * @var ServerRequestInterface
     */
    private $requestProphecy;

    /**
     * @var SessionInterface
     */
    private $sessionProphecy;

    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var StatesCollection
     */
    private $statesProphecy;

    /**
     * @var WorkflowState
     */
    private $stateProphecy;

    public function setUp(): void
    {
        $this->rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $this->urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $this->authenticatorProphecy = $this->prophesize(AuthenticationInterface::class);

        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->sessionProphecy = $this->prophesize(SessionInterface::class);

        $this->statesProphecy = $this->prophesize(StatesCollection::class);


        $this->statesProphecy->has(AddLpa::class)->willReturn(true);
        $this->statesProphecy->get(AddLpa::class)->willReturn(new AddLpa());

        $csrfProphecy = $this->prophesize(CsrfGuardInterface::class);
        $csrfProphecy->generateToken()
            ->willReturn(self::CSRF_CODE);
        $csrfProphecy->validateToken(self::CSRF_CODE)
            ->willReturn(true);
        $this->requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($csrfProphecy->reveal());

        $this->requestProphecy->getAttribute('session', null)
            ->willReturn($this->sessionProphecy->reveal());

        $this->requestProphecy->getAttribute(StatePersistenceMiddleware::WORKFLOW_STATE_ATTRIBUTE)
            ->willReturn($this->statesProphecy->reveal());

        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    public function testGetReturnsHtmlResponse()
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET');

        $this->rendererProphecy->render(
            'actor::add-lpa/activation-code',
            new CallbackToken(function ($options) {
                $this->assertIsArray($options);
                $this->assertArrayHasKey('form', $options);
                $this->assertInstanceOf(ActivationCode::class, $options['form']);

                return true;
            })
        )
            ->willReturn('');

        //  Set up the handler
        $handler = new ActivationCodeHandler(
            $this->rendererProphecy->reveal(),
            $this->authenticatorProphecy->reveal(),
            $this->urlHelperProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $response = $handler->handle($this->requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
