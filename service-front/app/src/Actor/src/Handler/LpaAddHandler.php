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
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class LpaAddHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
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

                //  Convert the date of birth
                $session->set(
                    'dob',
                    [
                        'day' => $postData['dob']['day'],
                        'month' => $postData['dob']['month'],
                        'year' => $postData['dob']['year']
                    ]
                );

                //  Set the data in the session and pass to the check handler
                $session->set('passcode', $postData['passcode']);
                $session->set('reference_number', $postData['reference_number']);

                return $this->redirectToRoute('lpa.check');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::lpa-add', [
            'form' => $form->prepare(),
            'user' => $this->getUser($request)
        ]));
    }
}
