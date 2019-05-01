<?php

declare(strict_types=1);

namespace Viewer\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Viewer\Middleware\Csrf\TokenManagerMiddleware;

trait Form
{
    /**
     * Creates a form using the passed in FormFactory and specified form class.
     *
     * Optionally binds to a POPO entity representing the form data.
     *
     * @param ServerRequestInterface $request
     * @param FormFactoryInterface $formFactory
     * @param string $formType
     * @param null|mixed $entity
     * @return FormInterface
     */
    public function createForm(ServerRequestInterface $request, FormFactoryInterface $formFactory, string $formType, $entity = null) : FormInterface
    {
        return $formFactory->create($formType, $entity, [
            //'csrf_token_manager' => $request->getAttribute(TokenManagerMiddleware::TOKEN_ATTRIBUTE)
        ]);
    }
}
