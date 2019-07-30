<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\I18n\Translator\TranslatorInterface;

/**
 * Class HomePageHandler
 * @package Viewer\Handler
 */
class HomePageHandler extends AbstractHandler
{
    private $translator;

    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper, TranslatorInterface $t)
    {
        parent::__construct($renderer, $urlHelper);
        $this->translator = $t;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('viewer::home-page', [
            'homepage_title' => $this->translator->translate('homepage_title')
        ]));
    }
}
