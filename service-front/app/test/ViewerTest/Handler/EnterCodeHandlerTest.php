<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
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
    /** @var string  */
    const CSRF_TOKEN = 'csrf_token_123';

    public function testSimplePageGet()
    {
        $formProphecy = $this->prophesize(ShareCode::class);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', [
                'form' => $formProphecy->reveal(),
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $formFactoryProphecy = $this->getFormFactoryProphecy($formProphecy);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal(), $formFactoryProphecy->reveal());

        $response = $handler->handle($this->getRequestProphecy()->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostNoLpaFound()
    {
        $formProphecy = $this->prophesize(ShareCode::class);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::enter-code', [
                'form' => $formProphecy->reveal(),
            ])
            ->willReturn('');

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $formFactoryProphecy = $this->getFormFactoryProphecy($formProphecy, [
            'lpa_code' => '9876-5432-1098',
        ]);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal(), $formFactoryProphecy->reveal());

        $response = $handler->handle($this->getRequestProphecy()->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostLpaFound()
    {
        $lpaId = '123456789012';

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $urlHelperProphecy->generate('view-lpa', [
                'id' => $lpaId,
            ], [])
            ->willReturn('/view-lpa/' . $lpaId);

        $lpa = new ArrayObject([
            'id' => $lpaId
        ], ArrayObject::ARRAY_AS_PROPS);

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getLpaByCode('1234-5678-9012')
            ->willReturn($lpa);

        $formProphecy = $this->prophesize(ShareCode::class);

        $formFactoryProphecy = $this->getFormFactoryProphecy($formProphecy, [
            'lpa_code' => '1234-5678-9012',
        ]);

        //  Set up the handler
        $handler = new EnterCodeHandler($rendererProphecy->reveal(), $urlHelperProphecy->reveal(), $lpaServiceProphecy->reveal(), $formFactoryProphecy->reveal());

        $response = $handler->handle($this->getRequestProphecy()->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    private function getRequestProphecy()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy->set('test', 'hello');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('session', null)
            ->willReturn($sessionProphecy->reveal());

        $requestProphecy->getAttribute(TokenManagerMiddleware::TOKEN_ATTRIBUTE)
            ->willReturn(self::CSRF_TOKEN);

        return $requestProphecy;
    }

    /**
     * @param ObjectProphecy $formProphecy
     * @param array $returnData
     * @return ObjectProphecy
     */
    private function getFormFactoryProphecy(ObjectProphecy $formProphecy, array $returnData = null)
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
            ->willReturn($formProphecy->reveal());

        $formFactoryProphecy = $this->prophesize(FormFactoryInterface::class);

        $formFactoryProphecy->create(ShareCode::class, null, [
                'csrf_token_manager' => self::CSRF_TOKEN,
            ])
            ->willReturn($symfonyFormProphecy->reveal());

        return $formFactoryProphecy;
    }
}
