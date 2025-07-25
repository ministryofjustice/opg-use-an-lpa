<?php

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

declare(strict_types=1);

use Common\Middleware\Routing\ConditionalRoutingMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Viewer\Handler\PaperVerification\CheckAnswersHandler;
use Viewer\Handler\PaperVerification\CheckLpaCodeHandler;
use Viewer\Handler\PaperVerification\AttorneyDateOfBirthHandler;
use Viewer\Handler\PaperVerification\LpaNotFoundHandler;
use Viewer\Handler\PaperVerification\PVDonorDateOfBirthHandler;
use Viewer\Handler\PaperVerification\NumberOfAttorneysHander;

$viewerRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/healthcheck', Common\Handler\HealthcheckHandler::class, 'healthcheck');
    $app->route('/home', Viewer\Handler\EnterPVSCodeHandler::class, ['GET', 'POST'], 'home');
    $app->route('/', Viewer\Handler\EnterPVSCodeHandler::class, ['GET', 'POST'], 'home-trial');
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

    //Paper Verification Code journey
    $app->route('/paper-verification/check-code',
                CheckLpaCodeHandler::class,
                ['GET', 'POST'],
                'pv.check-code');
    $app->route('/paper-verification/verification-code-sent-to',
                Viewer\Handler\PaperVerificationCodeSentToHandler::class,
                ['GET', 'POST'],
                'pv.verification-code-sent-to');

    $app->route('/paper-verification/provide-attorney-details',
                Viewer\Handler\ProvideAttorneyDetailsForPVHandler::class,
                ['GET', 'POST'],
                'pv.provide-attorney-details');

    $app->route('/paper-verification/donor-dob',
                PVDonorDateOfBirthHandler::class,
                ['GET', 'POST'],
                'donor-dob');

    $app->route('/paper-verification/attorney-dob',
                AttorneyDateOfBirthHandler::class,
                ['GET', 'POST'],
                'attorney-dob');

    $app->route('/paper-verification/number-of-attorneys',
                NumberOfAttorneysHander::class,
                ['GET', 'POST'],
                'number-of-attorneys');

    $app->route('/paper-verification/check-answers',
                CheckAnswersHandler::class,
                ['GET', 'POST'],
                'check-answers');

    $app->route('/paper-verification/lpa-not-found',
                LpaNotFoundHandler::class,
                ['GET', 'POST'],
                'lpa-not-found');
};

$actorRoutes = function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $DELETE_LPA_FEATURE = 'delete_lpa_feature';

    $defaultNotFoundPage = Actor\Handler\LpaDashboardHandler::class;

    $app->route('/home', Actor\Handler\AuthenticateOneLoginHandler::class, ['GET', 'POST'], 'home');
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
    $app->route('/create-account',Common\Handler\GoneHandler::class,['GET', 'POST'],'create-account');

    $app->get('/create-account-success',Common\Handler\GoneHandler::class,'create-account-success');
    $app->get(
        '/activate-account/{token}',
        fn () => new \Laminas\Diactoros\Response\RedirectResponse('/home'),
        'activate-account'
    );

    // User auth
    $app->route('/login', fn () => new \Laminas\Diactoros\Response\RedirectResponse('/home'), ['GET', 'POST'], 'login');

    $app->get('/session-expired', Actor\Handler\ActorSessionExpiredHandler::class, 'session-expired');
    $app->get('/session-check', Actor\Handler\ActorSessionCheckHandler::class, 'session-check');
    $app->get('/session-refresh', Common\Handler\SessionRefreshHandler::class, 'session-refresh');
    $app->get('/home/login', Actor\Handler\OneLoginCallbackHandler::class, 'auth-redirect');

    $app->get(
        '/logout',
        [
            Common\Middleware\Authentication\AuthenticationMiddleware::class,
            Actor\Handler\LogoutPageHandler::class
        ],
        'logout'
    );

    // User management
    $app->route('/reset-password', Common\Handler\GoneHandler::class, ['GET', 'POST'], 'password-reset');


    $app->route(
        '/reset-password/{token}',
        Common\Handler\GoneHandler::class,
        ['GET', 'POST'],
        'password-reset-token'
    );

    $app->get(
        '/verify-new-email/{token}',
        Common\Handler\GoneHandler::class,
        'verify-new-email'
    );

    // User deletion
    $app->get('/confirm-delete-account', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmDeleteAccountHandler::class], 'confirm-delete-account');

    $app->get('/delete-account', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\DeleteAccountHandler::class], 'delete-account');

    // User details
    $app->get('/settings', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\SettingsHandler::class,
    ], 'settings');

    $app->route('/change-password', Common\Handler\GoneHandler::class, ['GET','POST'], 'change-password');
    $app->route('/change-email', Common\Handler\GoneHandler::class, ['GET','POST'], 'change-email');

    $app->get('/lpa/change-details', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeDetailsHandler::class
    ], 'lpa.change-details');

    // LPA management
    $app->get('/lpa/dashboard', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaDashboardHandler::class
    ], 'lpa.dashboard');
    $app->route('/lpa/check', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckLpaHandler::class
    ], ['GET', 'POST'], 'lpa.check');
    $app->get('/lpa/view-lpa', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ViewLpaSummaryHandler::class
    ], 'lpa.view');
    $app->get('/lpa/view-lpa/images', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ViewLpaSummaryImagesHandler::class
    ], 'lpa.view-images');
    $app->route('/lpa/code-make', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CreateViewerCodeHandler::class
    ], ['GET', 'POST'], 'lpa.create-code');
    $app->route('/lpa/access-codes', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CheckAccessCodesHandler::class
    ], ['GET', 'POST'], 'lpa.access-codes');
    $app->post('/lpa/confirm-cancel-code', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ConfirmCancelCodeHandler::class
    ], 'lpa.confirm-cancel-code');
    $app->post('/lpa/cancel-code', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\CancelCodeHandler::class
    ], 'lpa.cancel-code');
    $app->get('/lpa/removed', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LpaRemovedHandler::class
    ], 'lpa.removed');
    $app->get('/lpa/instructions-preferences', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\InstructionsPreferencesHandler::class
    ], 'lpa.instructions-preferences');
    $app->get('/lpa/instructions-preferences-signed-before-2016', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Common\Handler\InstructionsPreferencesBefore2016Handler::class
    ], 'lpa.instructions-preferences-before-2016');
    $app->get('/lpa/death-notification', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\DeathNotificationHandler::class
    ], 'lpa.death-notification');
    $app->get('/lpa/change-lpa-details', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\ChangeLpaDetailsHandler::class
    ], 'lpa.change-lpa-details');

    //Add by code routes
    $app->route('/lpa/add-by-key/activation-key', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\AddLpa\ActivationKeyHandler::class,
    ], ['GET', 'POST'], 'lpa.add-by-key');

    $app->route('/lpa/add-by-key/date-of-birth', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\AddLpa\DateOfBirthHandler::class,
    ], ['GET', 'POST'], 'lpa.add-by-key.date-of-birth');

    $app->route('/lpa/add-by-key/lpa-reference-number', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\AddLpa\LpaReferenceNumberHandler::class
    ], ['GET', 'POST'], 'lpa.add-by-key.lpa-reference-number');

    // Access for All Journey
    $app->route('/lpa/add/contact-details', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\ContactDetailsHandler::class
    ], ['GET', 'POST'], 'lpa.add.contact-details');

    $app->route('/lpa/add/actor-role', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\ActorRoleHandler::class
    ], ['GET', 'POST'], 'lpa.add.actor-role');

    $app->route('/lpa/add/donor-details', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\DonorDetailsHandler::class
    ], ['GET', 'POST'], 'lpa.add.donor-details');

    $app->route('/lpa/add/actor-address', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\ActorAddressHandler::class
    ], ['GET', 'POST'], 'lpa.add.actor-address');

    $app->route('/lpa/add/attorney-details', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\AttorneyDetailsHandler::class
    ], ['GET', 'POST'], 'lpa.add.attorney-details');

    $app->route('/lpa/add/check-details-and-consent', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\CheckDetailsAndConsentHandler::class
    ], ['GET', 'POST'], 'lpa.add.check-details-and-consent');

    $app->route('/lpa/add/address-on-paper', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\AddressOnPaperHandler::class
    ], ['GET', 'POST'], 'lpa.add.address-on-paper');

    // Older LPA journey
    // if flag true, send user to triage page as entry point
    $app->route('/lpa/add', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\AddLpaTriageHandler::class,
    ], ['GET', 'POST'], 'lpa.add');

    $app->route('/lpa/add-by-paper-information', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\RequestActivationKeyInfoHandler::class
    ], ['GET', 'POST'], 'lpa.add-by-paper-information');

    $app->route('/lpa/request-code/lpa-reference-number', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\ReferenceNumberHandler::class
    ], ['GET', 'POST'], 'lpa.add-by-paper');

    $app->route('/lpa/request-code/your-name', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\NameHandler::class
    ], ['GET', 'POST'], 'lpa.your-name');

    $app->route('/lpa/request-code/date-of-birth', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\DateOfBirthHandler::class
    ], ['GET', 'POST'], 'lpa.date-of-birth');

    $app->route('/lpa/request-code/postcode', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\PostcodeHandler::class
    ], ['GET', 'POST'], 'lpa.postcode');

    $app->route('/lpa/request-code/check-answers', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\CheckYourAnswersHandler::class
    ], ['GET', 'POST'], 'lpa.check-answers');

    $app->post('/lpa/confirm-activation-key-generation', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\RequestActivationKey\CreateActivationKeyHandler::class
    ], 'lpa.confirm-activation-key-generation');

    $app->route('/lpa/remove-lpa', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        new ConditionalRoutingMiddleware(
            $container,
            $factory,
            $DELETE_LPA_FEATURE,
            Actor\Handler\RemoveLpaHandler::class,
            $defaultNotFoundPage
        )
    ], ['GET', 'POST'], 'lpa.remove-lpa');

    $app->get('/login-notification', [
        Common\Middleware\Authentication\AuthenticationMiddleware::class,
        Actor\Handler\LoginNotificationHandler::class
    ], 'login-notification');
};

return match (getenv('CONTEXT')) {
    'viewer' => $viewerRoutes,
    'actor' => $actorRoutes,
    default => throw new Error('Unknown context'),
};
