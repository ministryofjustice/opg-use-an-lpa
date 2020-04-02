<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\ConfirmDeleteAccount;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ConfirmDeleteAccountHandler
 * @package Actor\Handler
 */
class ConfirmDeleteAccountHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $accountId = $request->getParsedBody()['account_id'];
        $email = $request->getParsedBody()['user_email'];

        $form = new ConfirmDeleteAccount($this->getCsrfGuard($request));
        $form->setAttribute('action', $this->urlHelper->generate('lpa.delete-account'));

        $form->setData([
            'account_id' => $accountId,
            'user_email' => $email
        ]);

        return new HtmlResponse($this->renderer->render('actor::confirm-delete-account', [
            'form' => $form->prepare()
        ]));

    }

}
