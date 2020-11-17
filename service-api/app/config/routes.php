<?php

declare(strict_types=1);

use App\Handler\LpasCollectionHandler;
use Psr\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/{id}', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/{id}', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/{id}', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $app->get('/healthcheck', App\Handler\HealthcheckHandler::class, 'healthcheck');

    $app->get('/v1/lpas', App\Handler\LpasCollectionHandler::class, 'lpa.collection');
    $app->get('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}', App\Handler\LpasResourceHandler::class, 'lpa.resource');
    $app->delete('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}', App\Handler\LpasResourceHandler::class, 'lpa.remove');
    $app->post('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes', App\Handler\LpasResourceCodesCollectionHandler::class, 'lpa.create.code');
    $app->get('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes', App\Handler\LpasResourceCodesCollectionHandler::class, 'lpa.get.codes');
    $app->put('/v1/lpas/{user-lpa-actor-token:[0-9a-f\-]+}/codes', App\Handler\LpasResourceCodesCollectionHandler::class, 'lpa.cancel.code');

    $app->post('/v1/actor-codes/summary', App\Handler\ActorCodeSummaryHandler::class, 'lpa.actor-code.summary');
    $app->post('/v1/actor-codes/confirm', App\Handler\ActorCodeConfirmHandler::class, 'lpa.actor-code.confirm');

    $app->post('/v1/viewer-codes/summary', App\Handler\ViewerCodeSummaryHandler::class, 'lpa.viewer-code.summary');
    $app->post('/v1/viewer-codes/full', App\Handler\ViewerCodeFullHandler::class, 'lpa.viewer-code.full');

    $app->get('/v1/user', App\Handler\UserHandler::class, 'user.get');
    $app->post('/v1/user', App\Handler\UserHandler::class, 'user.create');
    $app->patch('/v1/user-activation', App\Handler\UserActivateHandler::class, 'user.activate');

    $app->patch('/v1/request-password-reset', App\Handler\RequestPasswordResetHandler::class, 'user.password-reset');
    $app->get('/v1/can-password-reset', App\Handler\CanPasswordResetHandler::class, 'user.can-password-reset');
    $app->patch('/v1/complete-password-reset', App\Handler\CompletePasswordResetHandler::class, 'user.complete-password-reset');

    $app->patch('/v1/request-change-email', App\Handler\RequestChangeEmailHandler::class, 'user.request-change-email');
    $app->get('/v1/can-reset-email', App\Handler\CanResetEmailHandler::class, 'user.can-reset-email');
    $app->patch('/v1/complete-change-email', App\Handler\CompleteChangeEmailHandler::class, 'user.complete-change-email');
    $app->patch('/v1/change-password', App\Handler\ChangePasswordHandler::class, 'user.change-password');
    $app->delete('/v1/delete-account/{account-id:[0-9a-f\-]+}', App\Handler\CompleteDeleteAccountHandler::class, 'user.delete-account');

    $app->patch('/v1/auth', App\Handler\AuthHandler::class, 'user.auth');
};
