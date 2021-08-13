<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\DonorDetails;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class DonorDetailsHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class DonorDetailsHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->setAuthenticator($authentication);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);
        $session = $this->getSession($request, 'session');
        $form = new DonorDetails($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());
            if ($form->isValid()) {
                $postData = $form->getData();

                $session->set('donor_first_names', $postData['donor_first_names']);
                $session->set('donor_last_name', $postData['donor_last_name']);
                $session->set(
                    'donor_dob',
                    [
                        'day' => $postData['donor_dob']['day'],
                        'month' => $postData['donor_dob']['month'],
                        'year' => $postData['donor_dob']['year']
                    ]
                );

                return $this->redirectToRoute('lpa.add.contact-details');
            }
        }

        $form->setData($session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/donor-details', [
            'user' => $user,
            'form' => $form->prepare()
        ]));
    }
}
