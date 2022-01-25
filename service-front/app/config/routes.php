<?php

declare(strict_types=1);

const ALLOW_OLDER_LPAS = 'allow_older_lpas';
const USE_OLDER_LPA_JOURNEY = 'use_older_lpa_journey';
const DELETE_LPA_FEATURE = 'delete_lpa_feature';

use Common\Middleware\Routing\ConditionalRoutingMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

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

$defaultNotFoundPage = Actor\Handler\LpaDashboardHandler::class;


$viewerRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/healthcheck', Common\Handler\HealthcheckHandler::class, 'healthcheck');
    $app->route('/home', Viewer\Handler\EnterCodeHandler::class, ['GET', 'POST'], 'home');
    $app->route('/', Viewer\Handler\EnterCodeHandler::class, ['GET', 'POST'], 'home-trial');
    $app->route('/check-code', Viewer\Handler\CheckCodeHandler::class, ['GET', 'POST'], 'check-code');
    $app->get('/view-lpa', Viewer\Handler\ViewLpaHandler::class, 'view-lpa');
    $app->get('/download-lpa', Viewer\Handler\DownloadLpaHandler::class, 'download-lpa');
    $app->get('/terms-of-use', Viewer\Handler\ViewerTermsOfUseHandler::class, 'terms-of-use');
    $app->get('/privacy-notice', Viewer\Handler\ViewerPrivacyNoticeHandler::class, 'privacy-notice');
    $app->get('/stats', Viewer\Handler\StatsPageHandler::class, 'viewer-stats');
    $app->get('/session-expired', Viewer\Handler\ViewerSessionExpiredHandler::class, 'session-expired');
    $app->get('/session-check', Viewer\Handler\ViewerSessionCheckHandler::class, 'session-check');
    $app->get('/session-refresh', Common\Handler\SessionRefreshHandler::class, 'session-refresh');
    $app->route('/cookies', Common\Handler\CookiesPageHandler::class, ['GET', 'POST'], 'cookies');
    $app->get(
        '/accessibility-statement',
        Viewer\Handler\ViewerAccessibilityStatementHandler::class,
        'accessibility-statement'
    );
    $app->get('/contact-us', Common\Handler\ContactUsPageHandler::class, 'contact-us');
    $app->get(
        '/instructions-preferences-signed-before-2016',
        Common\Handler\InstructionsPreferencesBefore2016Handler::class,
        'lpa.instructions-preferences-before-2016'
    );
};

$actorRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) use (
    $defaultNotFoundPage
): void {
    $app->route('/home', Actor\Handler\ActorTriagePageHandler::class, ['GET', 'POST'], 'home');
    $app->route('/', Actor\Handler\ActorTriagePageHandler::class, ['GET', 'POST'], 'home-trial');
    $app->get('/healthcheck', Common\Handler\HealthcheckHandler::class, 'healthcheck');
    $app->get('/stats', Actor\Handler\StatsPageHandler::class, 'actor-stats');

    $app->route('/cookies', Common\Handler\CookiesPageHandler::class, ['GET', 'POST'], 'cookies');
    $app->get('/terms-of-use', [Actor\Handler\ActorTermsOfUseHandler::class], 'terms-of-use');
    $app->get('/privacy-notice', [Actor\Handler\ActorPrivacyNoticeHandler::class], 'privacy-notice');
    $app->get(
        '/accessibility-statement',
        Actor\Handler\ActorAccessibilityStatementHandler::class,
        'accessibility-statement'
    );
    $app->get('/contact-us', Common\Handler\ContactUsPageHandler::class, 'contact-us');

    // User creation
    $app->route('/create-account', Actor\Handler\CreateAccountHandler::class, ['GET', 'POST'], 'create-account');
    $app->get('/create-account-success', Actor\Handler\CreateAccountSuccessHandler::class, 'create-account-success');
    $app->get('/activate-account/{token}', Actor\Handler\ActivateAccountHandler::class, 'activate-account');

    // User auth
    $app->route('/login', Actor\Handler\LoginPageHandler::class, ['GET', 'POST'], 'login');
    $app->get('/logout', Actor\Handler\LogoutPageHandler::class, 'logout');
    $app->get('/session-expired', Actor\Handler\ActorSessionExpiredHandler::class, 'session-expired');
    $app->get('/session-check', Actor\Handler\ActorSessionCheckHandler::class, 'session-check');
    $app->get('/session-refresh', Common\Handler\SessionRefreshHandler::class, 'session-refresh');

    // User management
    $app->route(
        '/forgot-password',
        Actor\Handler\PasswordResetRequestPageHandler::class,
        ['GET', 'POST'],
        'password-reset'
    );
    $app->route(
        '/forgot-password/{token}',
        Actor\Handler\PasswordResetPageHandler::class,
        ['GET', 'POST'],
        'password-reset-token'
    );
    $app->get('/verify-new-email/{token}', [
        Actor\Handler\CompleteChangeEmailHandler::class,
    ], 'verify-new-email');

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
    ], 'your-details');
    $app->route('/change-password', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangePasswordHandler::class
    ], ['GET','POST'], 'change-password');
    $app->route('/change-email', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestChangeEmailHandler::class
    ], ['GET','POST'], 'change-email');
    $app->get('/lpa/change-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeDetailsHandler::class
    ], 'lpa.change-details');

    // LPA management
    $app->get('/lpa/dashboard', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaDashboardHandler::class
    ], 'lpa.dashboard');
    $app->route('/lpa/check', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckLpaHandler::class
    ], ['GET', 'POST'], 'lpa.check');
    $app->get('/lpa/view-lpa', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ViewLpaSummaryHandler::class
    ], 'lpa.view');
    $app->route('/lpa/code-make', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CreateViewerCodeHandler::class
    ], ['GET', 'POST'], 'lpa.create-code');
    $app->route('/lpa/access-codes', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckAccessCodesHandler::class
    ], ['GET', 'POST'], 'lpa.access-codes');
    $app->post('/lpa/confirm-cancel-code', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmCancelCodeHandler::class
    ], 'lpa.confirm-cancel-code');
    $app->post('/lpa/cancel-code', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CancelCodeHandler::class
    ], 'lpa.cancel-code');
    $app->get('/lpa/removed', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaRemovedHandler::class
    ], 'lpa.removed');
    $app->get('/lpa/instructions-preferences', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\InstructionsPreferencesHandler::class
    ], 'lpa.instructions-preferences');
    $app->get('/lpa/instructions-preferences-signed-before-2016', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Common\Handler\InstructionsPreferencesBefore2016Handler::class
    ], 'lpa.instructions-preferences-before-2016');
    $app->get('/lpa/death-notification', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\DeathNotificationHandler::class
    ], 'lpa.death-notification');
    $app->get('/lpa/change-lpa-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeLpaDetailsHandler::class
    ], 'lpa.change-lpa-details');

    // Access for All Journey
    $app->route('/lpa/add/contact-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        new ConditionalRoutingMiddleware(
            $container,
            ALLOW_OLDER_LPAS,
            Actor\Handler\RequestActivationKey\ContactDetailsHandler::class,
            $defaultNotFoundPage
        )
    ], ['GET', 'POST'], 'lpa.add.contact-details');

    $app->route('/lpa/add/actor-role', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        new ConditionalRoutingMiddleware(
            $container,
            ALLOW_OLDER_LPAS,
            \Actor\Handler\RequestActivationKey\ActorRoleHandler::class,
            $defaultNotFoundPage
        )
    ], ['GET', 'POST'], 'lpa.add.actor-role');

    $app->route('/lpa/add/donor-details', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        new ConditionalRoutingMiddleware(
            $container,
            ALLOW_OLDER_LPAS,
            \Actor\Handler\RequestActivationKey\DonorDetailsHandler::class,
            $defaultNotFoundPage
        )
    ], ['GET', 'POST'], 'lpa.add.donor-details');

    $app->route('/lpa/add/check-details-and-consent', [
        Mezzio\Authentication\AuthenticationMiddleware::class,
        new ConditionalRoutingMiddleware(
            $container,
            ALLOW_OLDER_LPAS,
            \Actor\Handler\RequestActivationKey\CheckDetailsAndConsentHandler::class,
            $defaultNotFoundPage
        )
    ], ['GET', 'POST'], 'lpa.add.check-details-and-consent');

    // Older LPA journey
        // if flag true, send user to triage page as entry point
        $app->route('/lpa/add', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\AddLpaTriageHandler::class,
                Actor\Handler\LpaAddHandler::class
            )
        ], ['GET', 'POST'], 'lpa.add');

        $app->route('/lpa/add-by-code', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\LpaAddHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.add-by-code');

        $app->route('/lpa/add-by-paper-information', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\RequestActivationKeyInfoHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.add-by-paper-information');

        $app->route('/lpa/request-code/lpa-reference-number', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\ReferenceNumberHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.add-by-paper');

        $app->route('/lpa/request-code/your-name', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\NameHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.your-name');

        $app->route('/lpa/request-code/date-of-birth', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\DateOfBirthHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.date-of-birth');

        $app->route('/lpa/request-code/postcode', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\PostcodeHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.postcode');

        $app->route('/lpa/request-code/check-answers', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\CheckYourAnswersHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.check-answers');

        $app->post('/lpa/confirm-activation-key-generation', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                USE_OLDER_LPA_JOURNEY,
                Actor\Handler\RequestActivationKey\CreateActivationKeyHandler::class,
                $defaultNotFoundPage
            )
        ], 'lpa.confirm-activation-key-generation');



        $app->route('/lpa/remove-lpa', [
            Mezzio\Authentication\AuthenticationMiddleware::class,
            new ConditionalRoutingMiddleware(
                $container,
                DELETE_LPA_FEATURE,
                Actor\Handler\RemoveLpaHandler::class,
                $defaultNotFoundPage
            )
        ], ['GET', 'POST'], 'lpa.remove-lpa');

};

switch (getenv('CONTEXT')) {
    case 'viewer':
        return $viewerRoutes;
    case 'actor':
        return $actorRoutes;
    default:
        throw new Error('Unknown context');
}
