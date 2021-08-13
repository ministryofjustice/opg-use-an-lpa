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
use Mezzio\Authentication\UserInterface;
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
        $form = new DonorDetails($this->getCsrfGuard($request));
        $form->setData($request->getParsedBody());
        $session = $this->getSession($request, 'session');

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $donorData = $form->getData();

            $dobString = sprintf(
                '%s-%s-%s',
                $donorData['dob']['year'],
                $donorData['dob']['month'],
                $donorData['dob']['day']
            );

            $session->set('donor_firstnames', $donorData['first_names']);
            $session->set('donor_lastname', $donorData['last_name']);
            $session->set('donor_dob', $dobString);

            return $this->redirectToRoute('lpa.add.contact-details');
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/donor-details', [
            'user' => $user,
            'form' => $form->prepare()
        ]));
    }
}
