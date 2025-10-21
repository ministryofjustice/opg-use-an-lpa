<?php

declare(strict_types=1);

use App\Handler\{AccessForAllLpaConfirmationHandler,
    AccessForAllLpaValidationHandler,
    AddLpaConfirmationHandler,
    AddLpaValidationHandler,
    CompleteDeleteAccountHandler,
    HealthcheckHandler,
    LpasCollectionHandler,
    LpasResourceCodesCollectionHandler,
    LpasResourceHandler,
    LpasResourceImagesCollectionHandler,
    NotifyHandler,
    OneLoginAuthenticationCallbackHandler,
    OneLoginAuthenticationLogoutHandler,
    OneLoginAuthenticationRequestHandler,
    PaperVerification\UsableHandler,
    PaperVerification\ValidateHandler,
    PaperVerification\ViewHandler,
    RequestCleanseHandler,
    SystemMessageHandler,
    ViewerCodeFullHandler,
    ViewerCodeSummaryHandler};
use App\Middleware\RequestObject\RequestObjectMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * @psalm-suppress UnusedClosureParam
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $app->get('/healthcheck', HealthcheckHandler::class, 'healthcheck');

    $app->get('/v1/lpas', LpasCollectionHandler::class, 'lpa.collection');

    $app->post(
        '/v1/older-lpa/validate',
        AccessForAllLpaValidationHandler::class,
        'lpa.older.validate'
    );
    $app->patch(
        '/v1/older-lpa/confirm',
        AccessForAllLpaConfirmationHandler::class,
        'lpa.older.confirm'
    );

    $app->post(
        '/v1/older-lpa/cleanse',
        RequestCleanseHandler::class,
        'lpa.older.cleanse'
    );

    $app->get('/v1/system-message', SystemMessageHandler::class, 'system.message');

    $app->get('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}', LpasResourceHandler::class, 'lpa.resource');
    $app->delete('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}', LpasResourceHandler::class, 'lpa.remove');
    $app->post(
        '/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes',
        LpasResourceCodesCollectionHandler::class,
        'lpa.create.code'
    );
    $app->get(
        '/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes',
        LpasResourceCodesCollectionHandler::class,
        'lpa.get.codes'
    );
    $app->put(
        '/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes',
        LpasResourceCodesCollectionHandler::class,
        'lpa.cancel.code'
    );
    $app->get(
        '/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/images',
        LpasResourceImagesCollectionHandler::class,
        'lpa.get.images'
    );

    $app->post('/v1/add-lpa/validate', AddLpaValidationHandler::class, 'lpa.add.validate');
    $app->post('/v1/add-lpa/confirm', AddLpaConfirmationHandler::class, 'lpa.add.confirm');

    $app->post(
        '/v1/viewer-codes/summary',
        $factory->pipeline(
            [
                RequestObjectMiddleware::class,
                ViewerCodeSummaryHandler::class,
            ],
        ),
        'lpa.viewer-code.summary'
    );
    $app->post(
        '/v1/viewer-codes/full',
        $factory->pipeline(
            [
                RequestObjectMiddleware::class,
                ViewerCodeFullHandler::class,
            ],
        ),
        'lpa.viewer-code.full'
    );

    $app->post(
        '/v1/paper-verification/usable',
        $factory->pipeline(
            [
                RequestObjectMiddleware::class,
                UsableHandler::class,
            ],
        ),
        'lpa.paper-verification.usable'
    );
    $app->post(
        '/v1/paper-verification/validate',
        $factory->pipeline(
            [
                RequestObjectMiddleware::class,
                ValidateHandler::class,
            ],
        ),
        'lpa.paper-verification.validate'
    );

    $app->post(
        '/v1/paper-verification/view',
        $factory->pipeline(
            [
                RequestObjectMiddleware::class,
                ViewHandler::class,
            ],
        ),
        'lpa.paper-verification.view'
    );

    $app->delete(
        '/v1/delete-account/{account-id:[0-9a-f\-]+}',
        CompleteDeleteAccountHandler::class,
        'user.delete-account'
    );

    $app->get('/v1/auth/start', OneLoginAuthenticationRequestHandler::class, 'user.auth-start');
    $app->post('/v1/auth/callback', OneLoginAuthenticationCallbackHandler::class, 'user.auth-callback');
    $app->put('/v1/auth/logout', OneLoginAuthenticationLogoutHandler::class, 'user.auth-logout');

    $app->post('/v1/email-user/{emailTemplate}', NotifyHandler::class, 'lpa.user.notify');
};
