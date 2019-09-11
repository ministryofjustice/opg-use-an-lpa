<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaAdd;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class LpaAddHandler
 * @package Actor\Handler
 */
class LpaAddHandler extends AbstractHandler implements CsrfGuardAware, UserAware
{
    use CsrfGuard;
    use User;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * LpaAddHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     * @param LpaService $lpaService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new LpaAdd($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  Attempt to retrieve an LPA using the form data
                $postData = $form->getData();

                //  Convert the date of birth
                $dobString = sprintf('%s-%s-%s', $postData['dob']['year'], $postData['dob']['month'], $postData['dob']['day']);

                try {
                    $lpaData = $this->lpaService->search($postData['passcode'], $postData['reference_number'], $dobString);

                    //  TODO - Do nothing for now - a confirmation screen will be added later
                    // @codeCoverageIgnoreStart
                    echo sprintf('OK - validation has passed but the LPA (ID: %s) has not been added', $lpaData->id);
                    echo '<br/>';
                    echo '<br/>';
                    print_r($lpaData);
                    echo '<br/>';
                    echo '<br/>';
                    echo '<a href="/lpa/add-details">Return to add screen</a>';
                    die();
                    // @codeCoverageIgnoreStop

                } catch (ApiException $aex) {
                    if ($aex->getCode() == StatusCodeInterface::STATUS_NOT_FOUND) {
                        //  Show LPA not found page
                        return new HtmlResponse($this->renderer->render('actor::lpa-not-found', [
                            'user' => $this->getUser($request)
                        ]));
                    } else {
                        throw $aex;
                    }
                }
            }
        }

        return new HtmlResponse($this->renderer->render('actor::lpa-add', [
            'form' => $form->prepare(),
            'user' => $this->getUser($request)
        ]));
    }
}
