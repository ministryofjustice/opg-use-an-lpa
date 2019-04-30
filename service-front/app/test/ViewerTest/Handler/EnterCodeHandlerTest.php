<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Viewer\Form\ShareCode;
use Viewer\Handler\EnterCodeHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Middleware\Csrf\TokenManagerMiddleware;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

class EnterCodeHandlerTest extends TestCase
{
    /** @var ObjectProphecy */
    private $formProphecy;

    /** @var ObjectProphecy */
    private $tokenManagerProphecy;

    public function setUp()
    {
        $this->formProphecy = $this->prophesize(ShareCode::class);

        $this->tokenManagerProphecy = $this->prophesize(CsrfTokenManagerInterface::class);
    }

    public function testSimplePageGet()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', [
                'form' => $this->formProphecy->reveal(),
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $formFactoryProphecy = $this->getFormFactoryProphecy();

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal(), $formFactoryProphecy->reveal());

        $response = $handler->handle($this->getRequestProphecy()->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormSubmitted()
    {
        $lpaId = '123456789012';

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $urlHelperProphecy->generate('check-code', [], [])
            ->willReturn('/check-code' . $lpaId);

        $lpa = new ArrayObject([
            'id' => $lpaId
        ], ArrayObject::ARRAY_AS_PROPS);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaByCode('1234-5678-9012')
            ->willReturn($lpa);

        $formFactoryProphecy = $this->getFormFactoryProphecy([
            'lpa_code' => '1234-5678-9012',
        ]);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal(), $formFactoryProphecy->reveal());

        $response = $handler->handle($this->getRequestProphecy()->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }


    /**
     * @return ObjectProphecy
     */
    private function getRequestProphecy()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('code', '1234-5678-9012');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('session', null)
            ->willReturn($sessionProphecy->reveal());

        $requestProphecy->getAttribute(TokenManagerMiddleware::TOKEN_ATTRIBUTE)
            ->willReturn($this->tokenManagerProphecy->reveal());

        return $requestProphecy;
    }

    /**
     * @param array $returnData
     * @return ObjectProphecy
     */
    private function getFormFactoryProphecy(array $returnData = null)
    {
        $symfonyFormProphecy = $this->prophesize(Form::class);
        $symfonyFormProphecy->handleRequest()
            ->willReturn();

        $symfonyFormProphecy->isSubmitted()
            ->willReturn(is_array($returnData));

        $symfonyFormProphecy->isValid()
            ->willReturn(is_array($returnData));

        if (empty($returnData)) {
            $symfonyFormProphecy->getData()
                ->shouldNotBeCalled();
        } else {
            $symfonyFormProphecy->getData()
                ->willReturn($returnData);
        }

        $symfonyFormProphecy->createView()
            ->willReturn($this->formProphecy->reveal());

        $formFactoryProphecy = $this->prophesize(FormFactoryInterface::class);

        $formFactoryProphecy->create(ShareCode::class, null, [
                'csrf_token_manager' => $this->tokenManagerProphecy->reveal(),
            ])
            ->willReturn($symfonyFormProphecy->reveal());

        return $formFactoryProphecy;
    }
}
