<?php

declare(strict_types=1);

use Common\Handler\HealthcheckHandler;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

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
 *     Zend\Expressive\Router\Route::HTTP_METHOD_ANY,
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
};

$actorRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void
{
    $app->get('/', Actor\Handler\HomePageHandler::class, 'home');
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

    // User details
    $app->get('/your-details', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\YourDetailsHandler::class,
    ], 'your-details');

    // LPA management
    $app->get('/lpa/dashboard', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaDashboardHandler::class
    ], 'lpa.dashboard');
    $app->route('/lpa/add-details', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaAddHandler::class
    ], ['GET', 'POST'], 'lpa.add');
    $app->route('/lpa/check', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckLpaHandler::class
    ], ['GET', 'POST'], 'lpa.check');
    $app->get('/lpa/view-lpa', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ViewLpaSummaryHandler::class
    ], 'lpa.view');
    $app->route('/lpa/code-make',[
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CreateViewerCodeHandler::class
    ], ['GET', 'POST'], 'lpa.create-code');
    $app->route('/lpa/access-codes',[
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckAccessCodesHandler::class
    ], ['GET', 'POST'], 'lpa.access-codes');
    $app->post('/lpa/confirm-cancel-code', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmCancelCodeHandler::class
    ],  'lpa.confirm-cancel-code');
    $app->post('/lpa/cancel-code', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CancelCodeHandler::class
    ],'lpa.cancel-code');
    $app->get('/lpa/change-details', [
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeDetailsHandler::class
    ], 'lpa.change-details');
};

switch (getenv('CONTEXT')){
    case 'viewer':
        return $viewerRoutes;
    case 'actor':
        return $actorRoutes;
    default:
        throw new Error('Unknown context');
}
