<?php

declare(strict_types=1);

use App\Handler\{AccessForAllLpaConfirmationHandler,
    AccessForAllLpaValidationHandler,
    AddLpaConfirmationHandler,
    AddLpaValidationHandler,
    AuthHandler,
    CanPasswordResetHandler,
    CanResetEmailHandler,
    ChangePasswordHandler,
    CompleteChangeEmailHandler,
    CompleteDeleteAccountHandler,
    CompletePasswordResetHandler,
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
    RequestChangeEmailHandler,
    RequestCleanseHandler,
    RequestPasswordResetHandler,
    SystemMessageHandler,
    UserActivateHandler,
    UserHandler,
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

    $app->get('/v1/user', UserHandler::class, 'user.get');
    $app->post('/v1/user', UserHandler::class, 'user.create');
    $app->patch('/v1/user-activation', UserActivateHandler::class, 'user.activate');

    $app->patch('/v1/request-password-reset', RequestPasswordResetHandler::class, 'user.password-reset');
    $app->get('/v1/can-password-reset', CanPasswordResetHandler::class, 'user.can-password-reset');
    $app->patch(
        '/v1/complete-password-reset',
        CompletePasswordResetHandler::class,
        'user.complete-password-reset'
    );

    $app->patch('/v1/request-change-email', RequestChangeEmailHandler::class, 'user.request-change-email');
    $app->get('/v1/can-reset-email', CanResetEmailHandler::class, 'user.can-reset-email');
    $app->patch(
        '/v1/complete-change-email',
        CompleteChangeEmailHandler::class,
        'user.complete-change-email'
    );
    $app->patch('/v1/change-password', ChangePasswordHandler::class, 'user.change-password');
    $app->delete(
        '/v1/delete-account/{account-id:[0-9a-f\-]+}',
        CompleteDeleteAccountHandler::class,
        'user.delete-account'
    );

    $app->patch('/v1/auth', AuthHandler::class, 'user.auth');

    $app->get('/v1/auth/start', OneLoginAuthenticationRequestHandler::class, 'user.auth-start');
    $app->post('/v1/auth/callback', OneLoginAuthenticationCallbackHandler::class, 'user.auth-callback');
    $app->put('/v1/auth/logout', OneLoginAuthenticationLogoutHandler::class, 'user.auth-logout');

    $app->post('/v1/email-user/{emailTemplate}', NotifyHandler::class, 'lpa.user.notify');
};
