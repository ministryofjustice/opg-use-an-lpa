<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaAdd;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
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
    use SessionTrait;
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
        LpaService $lpaService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request, 'session');

        $form = new LpaAdd($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  Attempt to retrieve an LPA using the form data
                $postData = $form->getData();

                //  Filter out dashes and whitespace
                $postData = str_replace('-', '', $postData);
                $postData = str_replace(' ', '', $postData);

                //  Convert the date of birth
                $dobString = sprintf('%s-%s-%s', $postData['dob']['year'], $postData['dob']['month'], $postData['dob']['day']);

                //  Set the data in the session and pass to the check handler
                $session->set('passcode', $postData['passcode']);
                $session->set('reference_number', $postData['reference_number']);
                $session->set('dob', $dobString);

                return $this->redirectToRoute('lpa.check');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::lpa-add', [
            'form' => $form->prepare(),
            'user' => $this->getUser($request)
        ]));
    }
}
