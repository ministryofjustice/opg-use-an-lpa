<?php

declare(strict_types=1);

use Common\Handler\HealthcheckHandler;
use Psr\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
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

$viewerRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void
{
    $app->get('/', Viewer\Handler\HomePageHandler::class, 'home');
    $app->get('/healthcheck', Common\Handler\HealthcheckHandler::class, 'healthcheck');
    $app->route('/enter-code', Viewer\Handler\EnterCodeHandler::class, ['GET', 'POST'], 'enter-code');
    $app->get('/check-code', Viewer\Handler\CheckCodeHandler::class, 'check-code');
    $app->get('/view-lpa', Viewer\Handler\ViewLpaHandler::class, 'view-lpa');
    $app->get('/download-lpa', Viewer\Handler\DownloadLpaHandler::class, 'download-lpa');
    $app->get('/terms-of-use', Viewer\Handler\ViewerTermsOfUseHandler::class, 'viewer-terms-of-use');
};

$actorRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void
{
    $app->get('/', Actor\Handler\HomePageHandler::class, 'home');
    $app->get('/start', Actor\Handler\StartPageHandler::class, 'start');
    $app->get('/healthcheck', Common\Handler\HealthcheckHandler::class, 'healthcheck');

    // User creation
    $app->route('/create-account', Actor\Handler\CreateAccountHandler::class, ['GET', 'POST'], 'create-account');
    $app->get('/create-account-success', Actor\Handler\CreateAccountSuccessHandler::class, 'create-account-success');
    $app->get('/activate-account/{token}', Actor\Handler\ActivateAccountHandler::class, 'activate-account');

    // User auth
    $app->route('/login', Actor\Handler\LoginPageHandler::class, ['GET', 'POST'], 'login');
    $app->get('/logout', Actor\Handler\LogoutPageHandler::class, 'logout');

    // User management
    $app->route('/forgot-password', Actor\Handler\PasswordResetRequestPageHandler::class, ['GET', 'POST'], 'password-reset');
    $app->route('/forgot-password/{token}', Actor\Handler\PasswordResetPageHandler::class, ['GET', 'POST'], 'password-reset-token');

    // User deletion
    $app->get('/confirm-delete-account', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmDeleteAccountHandler::class], 'confirm-delete-account');
    $app->get('/delete-account', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\DeleteAccountHandler::class], 'delete-account');

    // User details
    $app->get('/your-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\YourDetailsHandler::class,
    ],'your-details');
    $app->get('/lpa/terms-of-use', [
        Actor\Handler\ActorTermsOfUseHandler::class
    ], 'lpa.terms-of-use');
    $app->route('/change-password', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangePasswordHandler::class
    ], ['GET','POST'], 'change-password');
    $app->get('/lpa/change-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeDetailsHandler::class
    ], 'lpa.change-details');

    // LPA management
    $app->get('/lpa/dashboard', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaDashboardHandler::class
    ], 'lpa.dashboard');
    $app->route('/lpa/add-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaAddHandler::class
    ], ['GET', 'POST'], 'lpa.add');
    $app->route('/lpa/check', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckLpaHandler::class
    ], ['GET', 'POST'], 'lpa.check');
    $app->get('/lpa/view-lpa', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ViewLpaSummaryHandler::class
    ], 'lpa.view');
    $app->route('/lpa/code-make',[
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CreateViewerCodeHandler::class
    ], ['GET', 'POST'], 'lpa.create-code');
    $app->route('/lpa/access-codes',[
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckAccessCodesHandler::class
    ], ['GET', 'POST'], 'lpa.access-codes');
    $app->post('/lpa/confirm-cancel-code', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmCancelCodeHandler::class
    ],  'lpa.confirm-cancel-code');
    $app->post('/lpa/cancel-code', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CancelCodeHandler::class
    ],'lpa.cancel-code');
    $app->get('/lpa/removed', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaRemovedHandler::class
    ], 'lpa.removed');

};

switch (getenv('CONTEXT')){
    case 'viewer':
        return $viewerRoutes;
    case 'actor':
        return $actorRoutes;
    default:
        throw new Error('Unknown context');
}
