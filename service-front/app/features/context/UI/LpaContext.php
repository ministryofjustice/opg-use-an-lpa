<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ContextUtilities;
use DateTime;
use DateTimeImmutable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

use function PHPUnit\Framework\assertStringContainsString;

class LpaContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    private const VIEWER_CODE_SERVICE_GET_SHARE_CODES   = 'ViewerCodeService::getShareCodes';
    private const ADD_LPA_VALIDATE                      = 'AddLpa::validate';
    private const ADD_LPA_CONFIRM                       = 'AddLpa::confirm';
    private const LPA_SERVICE_GET_LPAS                  = 'LpaService::getLpas';
    private const LPA_SERVICE_GET_LPA_BY_ID             = 'LpaService::getLpaById';
    private const VIEWER_CODE_SERVICE_CREATE_SHARE_CODE = 'ViewerCodeService::createShareCode';
    private const VIEWER_CODE_SERVICE_CANCEL_SHARE_CODE = 'ViewerCodeService::cancelShareCode';
    private const REMOVE_LPA_INVOKE                     = 'RemoveLpa::__invoke';
    private const INPSERVICE_GET_BY_ID                  = 'InstAndPrefImagesService::getImagesById';
    private const SYSTEM_MESSAGE_SERVICE_GET_MESSAGES   = 'SystemMessageService::getMessages';

    private array $dashboardLPAs;
    private mixed $lpa;
    private string $userLpaActorToken;
    private int $actorId;
    private array $lpaData;
    private array $systemMessageData;
    private string $organisation;
    private string $accessCode;
    private string $userFirstName;
    private int $userId;
    private string $userSurname;
    private string $companyName;

    #[Given('/^A trust corporation has created an access code$/')]
    public function zaTrustCorporationHasCreatedAndAccessCode(): void
    {
        $trustCorpActor = [
        'type'    => 'primary-attorney',
        'details' => [
            'addresses'    => [
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'id'           => 0,
                    'postcode'     => '',
                    'town'         => '',
                    'type'         => 'Primary',
                ],
            ],
            'companyName'  => 'ABC Ltd',
            'dob'          => '',
            'email'        => 'string',
            'firstname'    => '',
            'id'           => 0,
            'middlenames'  => null,
            'salutation'   => '',
            'surname'      => '',
            'systemStatus' => true,
            'uId'          => '700000151998',
            ],
        ];
        $organisation   = 'Natwest';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $trustCorpActor,
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => [
                                0 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => $organisation,
                                ],
                                1 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => 'Another Organisation',
                                ],
                            ],
                            'ActorId'      => 700000151998,
                        ],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );
    }

    #[Then('/^I am taken to a page explaining why instructions and preferences are not available$/')]
    public function iAmTakenToAPageExplainingWhyInstructionsAndPreferencesAreNotAvailable(): void
    {
        $this->ui->assertPageContainsText('Preferences and instructions cannot be shown for this LPA');
    }

    #[Then('/^I am taken to the change LPA details page$/')]
    public function iAmTakenToTheChangeLPADetailsPage(): void
    {
        $this->ui->assertPageAddress('/lpa/change-lpa-details');
        $this->ui->assertPageContainsText('Let us know if something is incorrect on the LPA');
    }

    #[Then('/^I am taken to the remove an LPA confirmation page for (.*) lpa$/')]
    public function iAmTakenToTheRemoveAnLPAConfirmationPage($status): void
    {
        $this->ui->assertPageAddress('/lpa/remove-lpa');
        $this->ui->assertPageContainsText('Are you sure you want to remove this LPA?');

        if ($status === 'Registered') {
            $this->ui->assertPageContainsText('LPA is registered');
        }
        if ($status === 'Cancelled') {
            $this->ui->assertPageNotContainsText(
                'you will need to request a new activation key if you want to add the LPA back to your account'
            );
        }
    }

    #[Then('/^I can see the name of the trust corporation that created the access code$/')]
    public function iCanSeeTheNameOfTheTrustCorporationThatCreatedTheAccessCode(): void
    {
        $this->ui->assertPageContainsText('ABC Ltd');
    }

    #[Given('/^I cannot see my LPA on the dashboard$/')]
    public function iCannotSeeMyLPAOnTheDashboard(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageNotContainsText('Ian Deputy Deputy');
    }

    #[Given('/^I can see a flash message confirming that my LPA has been removed$/')]
    public function iCanSeeAFlashMessageConfirmingThatMyLPAHasBeenRemoved(): void
    {
        $this->ui->assertPageContainsText("You've removed Ian Deputy's health and welfare LPA");
    }

    #[When('/^I click on the Read more link$/')]
    public function iClickOnTheReadMoreLink(): void
    {
        $this->ui->clickLink('Read more');
    }

    #[When('/^I request to remove an LPA from my account without the lpa actor token$/')]
    public function iRequestToRemoveAnLPAFromMyAccountWithoutTheLpaActorToken(): void
    {
        $this->ui->visit('/lpa/remove-lpa');
    }

    #[When('/^I select that I have seen something incorrect in the LPA details$/')]
    public function iSelectThatIHaveSeenSomethingIncorrectInTheLPADetails(): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->clickLink('Seen something incorrect in the LPA details');
    }

    #[Given('/^My active codes are cancelled$/')]
    public function myActiveCodesAreCancelled(): void
    {
        // Not needed for this context
    }

    #[Then('/^The LPA is removed/')]
    public function theLPAIsRemoved(): void
    {
        // Not needed for this context
    }

    #[Given('/^I confirm that I want to remove the LPA from my account$/')]
    public function iConfirmThatIWantToRemoveTheLPAFromMyAccount(): void
    {
        // API call for removing an LPA from a users account
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['lpa' => $this->lpa]),
                self::REMOVE_LPA_INVOKE
            )
        );

        //API call for getting all the users added LPAs on the dashboard
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->pressButton('Yes, remove LPA');
    }

    #[Then('/^I receive an email confirming activation key request$/')]
    public function iReceiveAnEmailConfirmingActivationKeyRequest(): void
    {
        //Not needed for this context
    }

    #[Given('/^an attorney can be removed from acting on a particular LPA$/')]
    public function anAttorneyCanBeRemovedFromActingOnAParticularLpa(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am given a unique access code$/')]
    public function iAmGivenAUniqueAccessCode(): void
    {
        $this->ui->assertPageAddress('/lpa/code-make');
        $this->ui->assertPageContainsText('XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('Give this access code to ' . $this->organisation);
    }

    #[Then('/^I am given a unique access code and told (.*) images available in the summary$/')]
    public function iAmGivenAUniqueAccessCodeAndToldImagesAvailableInTheSummary($check): void
    {
        $this->ui->assertPageAddress('/lpa/code-make');
        if ($check === 'instructions and preferences') {
            $this->ui->assertPageContainsText('Scanned copies of the donor’s preferences and instructions will be shown in the LPA summary. You should check the scanned image - if it’s not clear, organisations may ask to see the paper LPA.');
        } elseif ($check === 'instructions') {
            $this->ui->assertPageContainsText('Scanned copies of the donor’s instructions will be shown in the LPA summary. You should check the scanned image - if it’s not clear, organisations may ask to see the paper LPA.');
        }
    }

    #[Given('/^I am inactive against the LPA on my account$/')]
    public function iAmInactiveAgainstTheLpaOnMyAccount(): void
    {
        $this->lpaData['actor']['details']['systemStatus'] = false;
    }

    #[Then('/^I am navigated to the instructions and preferences page$/')]
    public function iAmNavigatedToTheInstructionsAndPreferencesPage(): void
    {
        $this->ui->assertPageAddress('/lpa/instructions-preferences');
        $this->ui->assertPageContainsText('Preferences and instructions');
    }

    #[Given('/^I am on the activation key information page$/')]
    public function iAmOnTheActivationKeyInformationPage(): void
    {
        $this->ui->visit('/lpa/add-by-paper-information');
        $this->ui->assertPageContainsText('Ask for an activation key');
    }

    #[Given('/^I am on the add an LPA page$/')]
    public function iAmOnTheAddAnLPAPage(): void
    {
        $this->ui->visit('/lpa/add-by-key/activation-key');
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');
    }

    #[Given('/^I am on the add an LPA triage page$/')]
    public function iAmOnTheAddAnLPATriagePage(): void
    {
        $this->ui->visit('/lpa/add');
        $this->iAmTakenToTheAddAnLPATriagePage();
    }

    #[Then('/^I am taken to the add an LPA triage page$/')]
    public function iAmTakenToTheAddAnLPATriagePage(): void
    {
        $this->ui->assertPageAddress('/lpa/add');
        $this->ui->assertPageContainsText('Do you have an activation key to add an LPA?');
    }

    #[Given('/^I am on the change details page$/')]
    public function iAmOnTheChangeDetailsPage(): void
    {
        $this->ui->visit('/lpa/change-details');
        $this->ui->assertPageAddress('/lpa/change-details');
    }

    #[Given('/^I am on the check LPA page$/')]
    public function iAmOnTheCheckLPAPage(): void
    {
        $this->ui->assertPageAddress('/lpa/check');
    }

    #[Given('/^I am on the dashboard page$/')]
    public function iAmOnTheDashboardPage(): void
    {
        if (isset($this->dashboardLPAs)) {
            //API call for getting all the users added LPAs
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode($this->dashboardLPAs),
                    self::LPA_SERVICE_GET_LPAS
                )
            );


            foreach ($this->dashboardLPAs as $lpa) {
                $this->apiFixtures->append(
                    ContextUtilities::newResponse(
                        StatusCodeInterface::STATUS_OK,
                        json_encode([]),
                        self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
                    )
                );
            }
        }

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Given('/^I am on the death notification page$/')]
    public function iAmOnTheDeathNotificationPage(): void
    {
        $this->ui->visit('/lpa/death-notification');
    }

    #[Given('/^I am on the full lpa page$/')]
    public function iAmOnTheFullLpaPage(): void
    {
        $this->iAmOnTheDashboardPage();
        $this->iRequestToViewAnLPAWhichStatusIs('Registered');
        $this->theFullLPAIsDisplayedWithTheCorrect('This LPA is registered');
    }

    #[When('/^I am on the instructions and preferences page$/')]
    public function iAmOnTheInstructionsAndPreferencesPage(): void
    {
        $this->iAmOnTheDashboardPage();
        $this->iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage('Read more');
        $this->iAmNavigatedToTheInstructionsAndPreferencesPage();
    }

    #[Then('/^I am shown a not found error$/')]
    public function iAmShownANotFoundError(): void
    {
        $this->ui->assertResponseStatus(404);
    }

    #[Then('/^I am taken to page giving me information about asking for an activation key$/')]
    public function iAmTakenToPageToAskForAnActivationKey(): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper-information');
    }

    #[Then('/^I am taken to the GOV.UK settings page$/')]
    public function iAmTakenToTheGovUkSettingsPage(): void
    {
        $this->ui->assertPageAddress('https://home.account.gov.uk/security');
    }

    #[Then('/^I am redirected to the activation key page$/')]
    public function iAmRedirectedToTheActivationKeyPage(): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');
    }

    #[Then('/^I am taken to request an activation key form$/')]
    public function iAmTakenToRequestAnActivationKeyForm(): void
    {
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('lpa/request-code/lpa-reference-number');
        $this->ui->assertPageContainsText('What is the LPA reference number?');
    }

    #[Then('/^My filled answers have been cleared$/')]
    public function myFilledAnswersHaveBeenCleared(): void
    {
        $this->ui->assertPageNotContainsText('AB12CD34EF56');
    }

    #[Given('/^I go to the check lpa page without filling in all the pages$/')]
    public function iGoToTheCheckLPAPageWithoutFillingInAllThePages(): void
    {
        $this->ui->fillField('activation_key', 'AB12CD34EF56');
        $this->ui->pressButton('Continue');
        $this->ui->visit('/lpa/check');
    }

    #[Then('/^I am taken to the change details page$/')]
    public function iAmTakenToTheChangeDetailsPage(): void
    {
        $this->ui->assertPageAddress('lpa/change-details?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText("Let us know if a donor or attorney's details change");
    }

    #[Given('/^I am the donor$/')]
    public function iAmTheDonor(): void
    {
        $this->lpaData['actor']['type'] = 'donor';
        unset($this->lpaData['actor']['details']['systemStatus']);
    }

    #[Then('/^I am told that I have 2 LPAs in my account$/')]
    public function iAmToldThatIHave2LPAsInMyAccount(): void
    {
        $this->ui->assertPageContainsText('You have 2 LPAs in your account');
    }

    #[Given('/^I have added an additional LPA to my account$/')]
    public function iHaveAdded2LPAsToMyAccount(): void
    {
        for ($x = 0; $x < 2; $x++) {
            // change the token within the LPA data to match as it changes
            $this->lpaData['user-lpa-actor-token']         = $this->userLpaActorToken;
            $this->dashboardLPAs[$this->userLpaActorToken] = $this->lpaData;
            $this->userLpaActorToken                       = (string)(intval($this->userLpaActorToken) + 1);
        }
    }

    #[Then('/^I am told that my input is invalid because (.*)$/')]
    public function iAmToldThatMyInputIsInvalidBecause($reason): void
    {
        $this->ui->assertPageContainsText($reason);
    }

    #[When('/^I attempt to add the same LPA again$/')]
    public function iAttemptToAddTheSameLPAAgain(): void
    {
        $this->iAmOnTheAddAnLPAPage();

        // API call for checking add LPA data
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA already added',
                        'data'    => [
                            'donor'         => [
                                'uId'         => $this->lpa->donor->uId,
                                'firstname'   => $this->lpa->donor->firstname,
                                'middlenames' => $this->lpa->donor->middlenames,
                                'surname'     => $this->lpa->donor->surname,
                            ],
                            'caseSubtype'   => $this->lpa->caseSubtype,
                            'lpaActorToken' => $this->userLpaActorToken,
                        ],
                    ]
                ),
                self::ADD_LPA_VALIDATE
            )
        );

        $this->fillAddLpaPages('XYUPHWQRECHV', '05', '10', '1975', '700000000054');
    }

    #[Then('/^I can find out why this LPA has been removed from the account$/')]
    public function iCanFindOutWhyThisLPAHasBeenRemovedFromTheAccount(): void
    {
        $this->ui->clickLink('Why is this?');
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->assertPageContainsText("We've removed an LPA from your account");
    }

    #[Then('/^I can go back to the dashboard page$/')]
    public function iCanGoBackToTheDashboardPage(): void
    {
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->clickLink('Back');
        $this->ui->assertPageAddress('lpa/dashboard');
    }

    #[Given('/^I can see a flash message for the added LPA$/')]
    public function iCanSeeAFlashMessageForTheAddedLPA(): void
    {
        $this->ui->assertPageContainsText("You've added Ian Deputy's health and welfare LPA");
    }

    #[Then('/^I can see all of my access codes and their details$/')]
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails(): void
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertElementContainsText(
            '#accordion-default-content-1 dl.govuk-summary-list',
            'V - XYZ3 - 21AB - C987'
        );
        $this->ui->assertElementContainsText(
            '#accordion-default-content-1 dl.govuk-summary-list',
            'Ian Deputy'
        );
    }

    #[Then('/^I can see the relevant (.*) and (.*) of my access codes and their details$/')]
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle): void
    {
        $this->ui->assertPageContainsText($activeTitle);
        $this->ui->assertElementContainsText(
            '#accordion-default-content-1 dl.govuk-summary-list',
            'V - XYZ3 - 21AB - C987'
        );

        $this->ui->assertPageContainsText($inactiveTitle);
        $this->ui->assertElementContainsText(
            '#accordion-default-content-2 dl.govuk-summary-list',
            'V - ABC3 - 21AB - CXYZ'
        );
    }

    #[Then('/^I can see authority to use the LPA is revoked$/')]
    public function iCanSeeAuthorityToUseTheLpaIsRevoked(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        $code = [
            'SiriusUid'    => $this->lpa->uId,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => '2024-01-01T23:59:59+00:00',
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([0 => $code]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText('You are not an attorney on this LPA anymore.');
    }

    #[Then('/^I can see banner about existing LPAs before 2019$/')]
    public function iCanSeeBannerAboutExistingLPAsBefore2019(): void
    {
        $this->ui->assertElementOnPage('.govuk-notification-banner');
        $this->ui->assertElementContainsText('.govuk-notification-banner', '1 September 2019');
    }

    #[Then('/^I can see banner about existing LPAs after 2016$/')]
    public function iCanSeeBannerAboutExistingLPAsAfter2016(): void
    {
        $this->ui->assertElementOnPage('.govuk-notification-banner');
        $this->ui->assertElementContainsText('.govuk-notification-banner', '1 January 2016');
    }

    #[Then('/^I can see (.*) link along with the instructions or preference message$/')]
    public function iCanSeeReadMoreLink($readMoreLink): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText(
            'This LPA has preferences and instructions. '
        );

        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $readMoreLink = $page->findLink($readMoreLink);
        if ($readMoreLink === null) {
            throw new Exception($readMoreLink . ' link not found');
        }
    }

    #[Then('/^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/')]
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        $code1 = [
            'SiriusUid'    => $this->lpa->uId,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => (new DateTime())->modify($code1Expiry)->format('Y-m-d'),
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        $code2 = [
            'SiriusUid'    => $this->lpa->uId,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => (new DateTime())->modify($code2Expiry)->format('Y-m-d'),
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => $code1,
                        1 => $code2,
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText($noActiveCodes);
    }

    #[Then('/^I can see that no organisations have access to my LPA$/')]
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA(): void
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('No active codes');
    }

    #[Then('/^I can see the code has not been used to view the LPA$/')]
    public function iCanSeeTheCodeHasNotBeenUsedToViewTheLPA(): void
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('Not viewed');
    }

    /**
     * <Important: This LPA has instructions or preferences>
     */
    #[Then('/^I can see the message (.*)$/')]
    public function iCanSeeTheMessage($message): void
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText($message);
    }

    /**
     * <Important: This LPA has instructions or preferences>
     */
    #[Then('/^I cannot see the message (.*)$/')]
    public function iCanNotSeeTheMessage($message): void
    {
        $this->ui->assertPageNotContainsText($message);
    }

    #[Then('/^I can see the name of the organisation that viewed the LPA$/')]
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA(): void
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('TestOrg');
    }

    #[When('/^I cancel the organisation access code/')]
    public function iCancelTheOrganisationAccessCode(): void
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->pressButton("Cancel organisation's access");

        $this->iWantToBeAskedForConfirmationPriorToCancellation();
    }

    #[When('/^I cancel the viewer code/')]
    public function iCancelTheViewerCode(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-09-16T22:57:12.398570Z',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode'   => $this->accessCode,
                            'Cancelled'    => '2020-09-16T22:58:43+00:00',
                            'Expires'      => '2020-09-16T23:59:59+01:00',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );
    }

    #[Then('/^I cannot check existing or inactive access codes for the LPA$/')]
    public function iCannotCheckExistingOrInactiveAccessCodesForTheLpa(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/access-codes"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/access-codes');
        }
    }

    #[Then('/^I cannot make access codes for the LPA$/')]
    public function iCannotMakeAccessCodesForTheLpa(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/code-make"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/code-make');
        }
    }

    #[Then('/^I cannot view the LPA summary$/')]
    public function iCannotViewTheLpaSummary(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/view-lpa"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/view-lpa');
        }
    }

    #[When('/^I check my access codes/')]
    public function iCheckMyAccessCodes(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I click the Continue link$/')]
    public function iClickTheContinueLink(): void
    {
        $this->ui->clickLink('Continue');
    }

    #[When('/^I click the (.*) link in the instructions or preference message$/')]
    public function iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage($readMoreLink): void
    {
        $this->iCanSeeReadMoreLink($readMoreLink);
        $this->ui->clickLink($readMoreLink);
    }

    #[When('/^I click the (.*) to change a donor or attorneys details$/')]
    public function iClickTheToChangeADonorOrAttorneysDetails($link): void
    {
        $this->ui->assertPageAddress('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
        $this->ui->clickLink($link);
    }

    #[When('/^I click to check my access code now expired/')]
    public function iClickToCheckMyAccessCodeNowExpired(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2020-02-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I click to check my access codes$/')]
    public function iClickToCheckMyAccessCodes(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                'LpaService::getLPAById'
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => (new DateTime('yesterday'))->format('c'),
                            'Expires'      => (new DateTime('+1 month'))->setTime(23, 59, 59)->format('c'),
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I click to check my access codes that is used to view LPA$/')]
    public function iClickToCheckMyAccessCodesThatIsUsedToViewLPA(): void
    {
        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I click to check my active and inactive codes$/')]
    public function iClickToCheckMyActiveAndInactiveCodes(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-02-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                        1 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2020-02-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => 'ABC321ABCXYZ',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I click to check the viewer code has been cancelled which is now expired/')]
    public function iClickToCheckTheViewerCodeHasBeenCancelledWhichIsNowExpired(): void
    {
        $this->ui->clickLink('Check access codes');
    }

    #[When('/^I confirm cancellation of the chosen viewer code/')]
    public function iConfirmCancellationOfTheChosenViewerCode(): void
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        // API call to cancel code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_CANCEL_SHARE_CODE
            )
        );

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call for getShareCodes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0
                            => [
                                'SiriusUid'    => $this->lpa->uId,
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Organisation' => $this->organisation,
                                'UserLpaActor' => $this->userLpaActorToken,
                                'ViewerCode'   => $this->accessCode,
                                'Cancelled'    => '2021-01-02T23:59:59+00:00',
                                'Expires'      => '2021-01-02T23:59:59+00:00',
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId,
                            ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->pressButton('Yes, cancel code');
    }

    #[When('/^I do not confirm cancellation of the chosen viewer code$/')]
    public function iDoNotConfirmCancellationOfTheChosenViewerCode(): void
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call for getShareCodes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode'   => $this->accessCode,
                            'Expires'      => '2021-01-05T23:59:59+00:00',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->ui->pressButton('No, return to access codes');
    }

    #[When('/^I do not select an option for whether I have an activation key$/')]
    public function iDoNotSelectAnOptionForWhetherIHaveAnActivationKey(): void
    {
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I expect to be on the death notification page$/')]
    public function iExpectToBeOnTheDeathNotificationPage(): void
    {
        $this->ui->assertPageAddress('/lpa/death-notification');
    }

    #[When('/^I fill in the form and click the cancel button$/')]
    public function iFillInTheFormAndClickTheCancelButton(): void
    {
        // API call for finding all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');

        $this->ui->fillField('activation_key', 'T3STPA22C0D3');
        $this->ui->pressButton('Continue');

        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');

        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->clickLink('Your LPAs');
    }

    #[Given('/^I have 2 codes for one of my LPAs$/')]
    public function iHave2CodesForOneOfMyLPAs(): void
    {
        // Not needed for one this context
    }

    #[Given('/^I have added a (.*) LPA$/')]
    public function iHaveAddedALPA($lpaType): void
    {
        // Dashboard page

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData])
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([])
            )
        );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Given('I have added an LPA to my account')]
    public function iHaveAddedAnLpaToMyAccount(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->dashboardLPAs = [$this->userLpaActorToken => $this->lpaData];
    }

    #[Given('I have no LPAs in my account')]
    public function iHaveNoLpasInMyAccount(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );
    }

    #[Given('I have been given access to use an LPA via credentials')]
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json'));

        $this->userLpaActorToken = '987654321';
        $this->actorId           = 9;

        $this->lpaData = [
            'user-lpa-actor-token'       => $this->userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'primary-attorney',
                'details' => [
                    'addresses'    => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => null,
                    'dob'          => '1975-10-05',
                    'email'        => 'string',
                    'firstname'    => 'Ian',
                    'id'           => 0,
                    'middlenames'  => null,
                    'salutation'   => 'Mr',
                    'surname'      => 'Deputy',
                    'systemStatus' => true,
                    'uId'          => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpa'                        => $this->lpa,
            'added'                      => '2021-10-5 12:00:00',
        ];
    }

    #[Given('I have been given access to use an LPA via credentials which has a donor signature before 2016')]
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentialsSignedBefore2016(): void
    {
        $this->lpa = json_decode(file_get_contents(
            __DIR__ . '../../../../test/fixtures/full_example_signed_before_2016.json'
        ));

        $this->userLpaActorToken = '987654321';
        $this->actorId           = 9;

        $this->lpaData = [
            'user-lpa-actor-token'       => $this->userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'primary-attorney',
                'details' => [
                    'addresses'    => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => null,
                    'dob'          => '1975-10-05',
                    'email'        => 'string',
                    'firstname'    => 'Ian',
                    'id'           => 0,
                    'middlenames'  => null,
                    'salutation'   => 'Mr',
                    'surname'      => 'Deputy',
                    'systemStatus' => true,
                    'uId'          => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpaDonorSignatureDate'      => '2015-06-30',
            'lpa'                        => $this->lpa,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData])
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([])
            )
        );
    }

    #[Given('I have added an LPA to my account which has a donor signature before 2016')]
    public function iHaveAddedAnLPAToMyAccountSignedBefore2016(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentialsSignedBefore2016();

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData])
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([])
            )
        );
    }

    #[Given('/^I have created an access code$/')]
    public function iHaveCreatedAnAccessCode(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';
    }

    #[Given('/^I have generated an access code for an organisation and can see the details$/')]
    public function iHaveGeneratedAnAccessCodeForAnOrganisationAndCanSeeTheDetails(): void
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iAmOnTheDashboardPage();
        $this->iClickToCheckMyAccessCodes();
        $this->iCanSeeAllOfMyAccessCodesAndTheirDetails();
    }

    #[Given('I have generated an access code for an LPA on my account')]
    public function iHaveGeneratedAnAccessCodeForAnLPAOnMyAccount(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                )
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => (new DateTime('yesterday'))->format('c'),
                            'Expires'      => (new DateTime('+1 month'))->setTime(23, 59, 59)->format('c'),
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                )
            )
        );
    }

    #[When('/^I have not provided required information for creating access code such as (.*)$/')]
    public function iHaveNotProvidedRequiredInformationForCreatingAccessCodeSuchAs($organisationname): void
    {
        $this->ui->assertPageContainsText('Which organisation do you want to give access to?');

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
            )
        );

        $this->ui->fillField('org_name', $organisationname);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I have shared the access code with organisations to view my LPA$/')]
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2026-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => '',
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2026-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => '',
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );
    }

    #[Given('/^I have shared the access code with organisations and they have viewed my LPA$/')]
    public function iHaveSharedTheAccessCodeWithOrganisationsAndTheyHaveViewedMyLPA(): void
    {
        $organisation = 'Natwest';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => [
                                0 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => $organisation,
                                ],
                                1 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => 'Another Organisation',
                                ],
                            ],
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $organisation = 'Natwest';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to get access codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => [
                                0 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => $organisation,
                                ],
                                1 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => 'Another Organisation',
                                ],
                            ],
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );
    }

    #[When('/^I navigate to check an access code$/')]
    public function iNavigateToCheckAnAccessCode(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode([]),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->visit('lpa/access-codes?lpa=' . $this->userLpaActorToken);
    }

    #[When('/^I navigate to give an organisation access$/')]
    public function iNavigateToGiveAnOrganisationAccess(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode([]),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->visit('lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    #[When('/^I navigate to view the LPA summary$/')]
    public function iNavigateToViewTheLpaSummary(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode([]),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->visit('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
    }

    #[When('/^I request an activation key without entering my (.*)$/')]
    public function iRequestAnActivationKeyWithoutEnteringMy($data): void
    {
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to add an LPA that does not exist$/')]
    public function iRequestToAddAnLPAThatDoesNotExist(): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');

        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode(
                    [
                        'title'   => 'Not found',
                        'details' => 'Code validation failed',
                        'data'    => [],
                    ]
                ),
                self::ADD_LPA_VALIDATE
            )
        );

        $this->fillAddLpaPages('ABC321GHI567', '05', '10', '1975', '700000000278');
    }

    #[When('/^I request to add an LPA whose status is (.*) using (.*)$/')]
    public function iRequestToAddAnLPAWhoseStatusIs(string $status, string $code): void
    {
        $this->lpa->status = $status;

        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');

        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA status is not registered',
                        'data'    => [],
                    ]
                ),
                self::ADD_LPA_VALIDATE
            )
        );

        $this->fillAddLpaPages($code, '05', '10', '1975', '700000000054');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);
        Assert::assertEquals('XYUPHWQRECHV', $params['actor-code']);
    }

    #[When('/^I request to add an LPA with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/')]
    public function iRequestToAddAnLPAWithAnInvalidDOBFormatOf1(string $day, string $month, string $year): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');
        $this->ui->fillField('activation_key', 'T3STPA22C0D3');
        $this->ui->pressButton('Continue');

        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to add an LPA with an invalid passcode format of "([^"]*)"$/')]
    public function iRequestToAddAnLPAWithAnInvalidPasscodeFormatOf1(string $activation_key): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');
        $this->ui->fillField('activation_key', $activation_key);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to add an LPA with an invalid reference number format of "([^"]*)"$/')]
    public function iRequestToAddAnLPAWithAnInvalidReferenceNumberFormatOf(string $referenceNo): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');
        $this->fillAddLpaPages('T3STPA22C0D3', '05', '10', '1975', $referenceNo);
    }

    #[When('/^I request to add an LPA with the code "([^"]*)" that is for "([^"]*)" "([^"]*)" and I will have an Id of ([^"]*)$/')]
    public function iRequestToAddAnLPAWithTheCodeThatIsForAndIWillHaveAnIdOf(
        string $activation_key,
        $firstName,
        $secondName,
        $id,
    ): void {
        $this->userId        = (int)$id;
        $this->actorId       = (int)$id;
        $this->userFirstName = $firstName;
        $this->userSurname   = $secondName;

        // API Response for LPA data request, configured with our specified details
        $this->lpaData = [
            'user-lpa-actor-token'       => $this->userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'primary-attorney',
                'details' => [
                    'addresses'    => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => null,
                    'id'           => $this->actorId,
                    'uId'          => '700000000054',
                    'dob'          => '1975-10-05',
                    'salutation'   => 'Mr',
                    'firstname'    => $this->userFirstName,
                    'middlenames'  => null,
                    'surname'      => $this->userSurname,
                    'systemStatus' => true,
                    'email'        => 'string',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpa'                        => $this->lpa,
        ];

        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->lpaData),
                self::ADD_LPA_VALIDATE
            )
        );

        $this->fillAddLpaPages($activation_key, '05', '10', '1975', '700000000054');
    }

    #[When('/^I as trust corporation request to add an LPA with the code "([^"]*)" that is for "([^"]*)" and I will have an Id of ([^"]*)$/')]
    public function iAsTrustCorporationRequestToAddAnLPAWithTheCodeThatIsForAndIWillHaveAnIdOf(
        string $activation_key,
        $companyName,
        $id,
    ): void {
        $this->userId      = (int)$id;
        $this->actorId     = (int)$id;
        $this->companyName = $companyName;

        // API Response for LPA data request, configured with our specified details
        $this->lpaData = [
            'user-lpa-actor-token'       => $this->userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'trust-corporation',
                'details' => [
                    'addresses'    => [
                        '0' => [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => 'trust corporation',
                    'dob'          => null,
                    'email'        => 'string',
                    'firstname'    => 'trust',
                    'id'           => $this->actorId,
                    'middlenames'  => null,
                    'salutation'   => 'Mr',
                    'surname'      => 'corporation',
                    'systemStatus' => true,
                    'uId'          => '700000151998',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpa'                        => $this->lpa,
        ];

        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->lpaData),
                self::ADD_LPA_VALIDATE
            )
        );

        $this->fillAddLpaPages($activation_key, '05', '10', '1975', '700000151998');
    }

    #[When('/^I request to add an LPA with valid details using (.*) which matches (.*)$/')]
    public function iRequestToAddAnLPAWithValidDetailsUsing(string $code, string $storedCode): void
    {
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');

        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->lpaData),
                self::ADD_LPA_VALIDATE
            ),
        );


        $this->fillAddLpaPages($code, '05', '10', '1975', '700000000054');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertEquals($storedCode, $params['actor-code']);
    }

    #[When('/^I request to give an organisation access$/')]
    public function iRequestToGiveAnOrganisationAccess(): void
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('/lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    #[When('/^I request to give an organisation access for my (.*) LPA$/')]
    public function iRequestToGiveAnOrganisationAccessForMyLPA(string $lpaType): void
    {
        $this->lpa->caseSubtype = $lpaType;

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->clickLink('Give an organisation access');
    }

    #[When('/^I request to give an organisation access to one of my LPAs which has "([^"]*)" and signed after 2016$/')]
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAsWhichHasIPSetting($check): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        if ($check === 'instructions and preferences') {
            $this->lpa->applicationHasRestrictions = true;
            $this->lpa->applicationHasGuidance     = true;
        } elseif ($check === 'instructions') {
            $this->lpa->applicationHasRestrictions = false;
        }

        // API call for get LpaById (when give organisation access is clicked)
        $this->iRequestToGiveAnOrganisationAccess();

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'code'         => $this->accessCode,
                        'expires'      => '2021-03-07T23:59:59+00:00',
                        'organisation' => $this->organisation,
                    ]
                ),
                self::VIEWER_CODE_SERVICE_CREATE_SHARE_CODE
            )
        );

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->fillField('org_name', $this->organisation);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to give an organisation access to one of my LPAs$/')]
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        // API call for get LpaById (when give organisation access is clicked)
        $this->iRequestToGiveAnOrganisationAccess();

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'code'         => $this->accessCode,
                        'expires'      => '2021-03-07T23:59:59+00:00',
                        'organisation' => $this->organisation,
                    ]
                ),
                self::VIEWER_CODE_SERVICE_CREATE_SHARE_CODE
            )
        );

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->fillField('org_name', $this->organisation);
        $this->ui->pressButton('Continue');
    }

    #[Given('/^I request to go back and try again$/')]
    public function iRequestToGoBackAndTryAgain(): void
    {
        $this->ui->pressButton('Try again');
        $this->ui->assertPageAddress('/lpa/add');
    }

    #[When('/^I request to remove an LPA from my account that is (.*)$/')]
    public function iRequestToRemoveAnLPAFromMyAccountThatIs($status): void
    {
        $this->lpa->status = $status;

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );
        $this->ui->clickLink('Remove LPA');
        $this->ui->assertPageAddress('/lpa/remove-lpa');
    }

    #[When('I request to remove an LPA from my account that has no active attorney on it')]
    public function iRequestToRemoveAnLPAFromMyAccountThatHasNoActiveAttorneysOnIt(): void
    {
        $this->lpa->status = 'Registered';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => null,
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->clickLink('Remove LPA');

        $this->ui->assertPageAddress('/lpa/remove-lpa');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
    }

    #[When('I request to view an LPA which has a donor signature before 2016')]
    #[When('I request to view an LPA which has a trust corporation added')]
    public function iRequestToViewAnLPAWhichHasADonorSignatureBefore2016(): void
    {
        $this->ui->assertPageContainsText('View LPA summary');

        $this->mockApiGetLpaByIdAndGetImagesById();
    }

    #[When('/^I request to view an LPA which status is "([^"]*)"$/')]
    public function iRequestToViewAnLPAWhichStatusIs($status): void
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->status = $status;

        if ($status === 'Revoked') {
            // API call for get LpaById
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date'                 => 'date',
                            'lpa'                  => [],
                            'actor'                => $this->lpaData['actor'],
                        ]
                    )
                )
            );
        } else {
            // API call for get LpaById
            $this->mockApiGetLpaByIdAndGetImagesById();
        }
    }

    #[When('/^I request to view a Combined LPA which status is "([^"]*)"$/')]
    public function iRequestToViewACombinedLPAWhichStatusIs($status): void
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->status = $status;

        if ($status === 'Revoked') {
            // API call for get LpaById
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date'                 => 'date',
                            'lpa'                  => [],
                            'actor'                => $this->lpaData['actor'],
                        ]
                    )
                )
            );
        } else {
            // API call for get LpaById
            $this->mockApiGetLpaByIdAndGetImagesById();
        }
    }

    #[When('/^I request to view the LPA that has already been added$/')]
    public function iRequestToViewTheLPAThatHasAlreadyBeenAdded(): void
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // InstAndPrefImagesService::getImagesById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'uId'        => (int) $this->lpa->uId,
                        'status'     => 'COLLECTION_COMPLETE',
                        'signedUrls' => [],
                    ]
                ),
                self::INPSERVICE_GET_BY_ID
            )
        );

        $this->ui->clickLink('see this LPA');
    }

    #[When('/^I say I do not have an activation key$/')]
    public function iSayIDoNotHaveAnActivationKey(): void
    {
        $this->ui->fillField('activation_key_triage', 'No');
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I see a page showing me the answers I have entered and content that helps me get it right$/')]
    public function iSeeAPageShowingMeTheAnswersIHaveEnteredAndContentThatHelpsMeGetItRight(): void
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
        $this->ui->assertPageContainsText('LPA reference number 700000000054');
        $this->ui->assertPageContainsText('Activation key C-XYUPHWQRECHV');
        $this->ui->assertPageContainsText('Date of birth 5 October 1975');
    }

    #[When('/^I select to add an LPA$/')]
    public function iSelectToAddAnLPA(): void
    {
        $this->ui->clickLink('Add another LPA');
    }

    #[When('/^I choose to add my first LPA$/')]
    public function iChooseToAddMyFirstLPA(): void
    {
        $this->ui->clickLink('Add your first LPA');
    }

    #[When('/^I select to find out more if the donor or an attorney dies$/')]
    public function iSelectToFindOutMoreIfTheDonorOrAnAttorneyDies(): void
    {
        $this->ui->clickLink('the donor or an attorney dies');
    }

    #[When('/^I select (.*) whether I have an activation key$/')]
    public function iSelectWhetherIHaveAnActivationKey($option): void
    {
        $this->ui->fillField('activation_key_triage', $option);
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I should be able to click a link to go and create the access codes$/')]
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes(): void
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('Which organisation do you want to give access to');
    }

    #[Then('/^I should be shown the details of the cancelled viewer code with cancelled status/')]
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus(): void
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('Inactive codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('Cancelled');
    }

    #[Then('/^I should be shown the details of the viewer code with status (.*)/')]
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus($status): void
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $codeDetails = [];

        $codeSummary = $page->findAll('css', '.govuk-summary-list__row');
        foreach ($codeSummary as $codeItem) {
            $codeDetails[] = $codeItem->find('css', 'dd')->getText();
        }

        Assert::assertEquals($codeDetails[0], 'V - XYZ3 - 21AB - C987');
        Assert::assertEquals($codeDetails[1], 'Ian Deputy');
        Assert::assertEquals($codeDetails[2], 'Not viewed');
        Assert::assertEquals($codeDetails[4], $status);

        if ($codeDetails === null) {
            throw new Exception('Code details not found');
        }
    }

    #[Then('/^I should be taken back to the access code summary page/')]
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage(): void
    {
        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageNotContainsText('Cancelled');
    }

    #[Then('/^I should be told access code could not be created due to (.*)$/')]
    public function iShouldBeToldAccessCodeCouldNotBeCreatedDueTo($reasons): void
    {
        $this->ui->assertPageAddress('/lpa/code-make');

        $this->ui->assertPageContainsText($reasons);
    }

    #[Then('/^I should be told that I have already added this LPA$/')]
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA(): void
    {
        $this->ui->assertPageContainsText("You've already added this LPA to your account");
    }

    #[Then('/^I should be told that I have not created any access codes yet$/')]
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet(): void
    {
        $this->ui->assertPageContainsText('Check access codes');
        $this->ui->assertPageContainsText('There are no access codes for this LPA');
        $this->ui->assertPageContainsText('Give an organisation access');
    }

    #[Given('/^I should not see a flash message to confirm the code that I have cancelled$/')]
    public function iShouldNotSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled(): void
    {
        $this->ui->assertPageNotContainsText(
            sprintf(
                'You cancelled the access code for %s: V-XYZ3-21AB-C987',
                $this->organisation
            )
        );
    }

    #[Given('/^I should see a flash message to confirm the code that I have cancelled$/')]
    public function iShouldSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled(): void
    {
        $this->ui->assertPageContainsText(
            sprintf(
                'You cancelled the access code for %s: V-XYZ3-21AB-C987',
                $this->organisation
            )
        );
    }

    #[Then('/^I should see relevant (.*) of organisations$/')]
    public function iShouldSeeRelevantOfOrganisations($orgDescription): void
    {
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText($orgDescription);
    }

    #[Then('/^I want to be asked for confirmation prior to cancellation/')]
    public function iWantToBeAskedForConfirmationPriorToCancellation(): void
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->ui->assertPageContainsText('Are you sure you want to cancel this code?');
    }

    #[When('/^I want to cancel the access code for an organisation$/')]
    public function iWantToCancelTheAccessCodeForAnOrganisation(): void
    {
        // Not needed for this context
    }

    #[Then('/^I want to see the option to cancel the code$/')]
    public function iWantToSeeTheOptionToCancelTheCode(): void
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText("Cancel organisation's access");
    }

    #[Then('/^I will be taken to the appropriate (.*) to add an lpa$/')]
    public function iWillBeTakenToTheAppropriateToAddAnLpa($page): void
    {
        $this->ui->assertPageContainsText($page);
    }

    #[Then('/^I will be told that I must select whether I have an activation key$/')]
    public function iWillBeToldThatIMustSelectWhetherIHaveAnActivationKey(): void
    {
        $this->ui->assertPageContainsText('Select if you have an activation key to add an LPA');
    }

    #[Then('/^The correct LPA is found and I can confirm to add it$/')]
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt(): void
    {
        // API call for adding an LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CREATED,
                json_encode(['user-lpa-actor-token' => $this->userLpaActorToken]),
                self::ADD_LPA_CONFIRM
            ),
        );

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->assertPageAddress('/lpa/check');

        $this->ui->assertPageContainsText('Confirm this is the correct LPA');
        $this->ui->assertPageContainsText('Mrs Ian Deputy Deputy');

        $this->ui->pressButton('Confirm');
    }

    #[Given('/^Has correct name "([^"]*)""([^"]*)" and role "([^"]*)"$/')]
    public function theNameMatchesExpected(string $firstName, string $lastName, string $role): void
    {
        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $summary = $page->findAll('css', '.govuk-summary-list__row');

        $foundName = null;
        $foundRole = null;
        foreach ($summary as $row) {
            $key     = $row->find('css', '.govuk-summary-list__key');
            $keyText = trim($key->getText());
            $value   = $row->find('css', '.govuk-summary-list__value');

            if ($keyText === 'Your role on this LPA') {
                $foundRole = $value->getText();
                assertStringContainsString($role, $foundRole);
            }

            if ($keyText === 'Your name') {
                $foundName = $value->getText();
                assertStringContainsString($firstName, $foundName);
                assertStringContainsString($lastName, $foundName);
            }
        }

        Assert::assertNotNull($foundRole, 'Your role on this LPA not found on this page');
        Assert::assertNotNull($foundName, "Actor's name was not found on this page");

        $this->ui->pressButton('Confirm');
    }

    #[Given('/^Has the correct company name "([^"]*)" and role "([^"]*)"$/')]
    public function hasTheCorrectCompanyNameAndRole(string $companyName, string $role): void
    {
        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $summary = $page->findAll('css', '.govuk-summary-list__row');

        $foundName = null;
        $foundRole = null;
        foreach ($summary as $row) {
            $key     = $row->find('css', '.govuk-summary-list__key');
            $keyText = trim($key->getText());
            $value   = $row->find('css', '.govuk-summary-list__value');

            if ($keyText === 'Your role on this LPA') {
                $foundRole = $value->getText();
                assertStringContainsString($role, $foundRole);
            }

            if ($keyText === 'Your name') {
                $foundName = $value->getText();
                assertStringContainsString($companyName, $foundName);
            }
        }

        Assert::assertNotNull($foundRole, 'Your role on this LPA not found on this page');
        Assert::assertNotNull($foundName, 'Company name was not found on this page');

        $this->ui->pressButton('Confirm');
    }

    #[Then('/^The LPA is found correctly$/')]
    public function theLpaIsFoundCorrectly(): void
    {
        // API call for adding an LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CREATED,
                json_encode(['user-lpa-actor-token' => $this->userLpaActorToken]),
                self::ADD_LPA_CONFIRM,
            )
        );

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->userLpaActorToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->assertPageAddress('/lpa/check');
    }

    #[Then('/^The full LPA is displayed$/')]
    public function theFullLPAIsDisplayed(): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa?=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('This LPA is registered');
    }

    #[Then('/^The full LPA is displayed with the correct (.*)$/')]
    public function theFullLPAIsDisplayedWithTheCorrect($message): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText($message);
    }

    #[Then('/^The Revoked LPA details are not displayed$/')]
    public function theRevokedLPADetailsAreNotDisplayed(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageNotContainsText(
            $this->lpa->donor->firstname . '' .
            $this->lpa->donor->middlenames . ' ' .
            $this->lpa->donor->surname
        );
    }

    #[Given('/^The LPA has not been added$/')]
    public function theLPAHasNotBeenAdded(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Then('/^The LPA is not found$/')]
    public function theLPAIsNotFound(): void
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
    }

    #[Given('/^The LPA is successfully added$/')]
    public function theLPAIsSuccessfullyAdded(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('Ian Deputy Deputy');
        $this->ui->assertPageContainsText('Health and welfare');
    }

    #[When('/^I check access codes of the status changed LPA$/')]
    public function iCheckAccessCodesOfTheStatusChangedLpa(): void
    {
        $this->lpa->status = 'Revoked';

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => [],
                        'actor'                => $this->lpaData['actor'],
                    ]
                )
            )
        );
    }

    #[When('/^The LPA has been revoked$/')]
    #[Then('/^I cannot see my access codes and their details$/')]
    public function theStatusOfTheLpaGotRevoked(): void
    {
        // Not needed for this context
    }

    #[Then('/^I request to give an organisation access to the LPA whose status changed to Revoked$/')]
    #[When('/^I request to view an LPA whose status changed to Revoked$/')]
    public function iRequestToGiveAnOrganisationAccessToTheLPAWhoseStatusChangedToRevoked(): void
    {
        $this->lpa->status             = 'Revoked';
        $this->lpa->donor->firstname   = 'abc';
        $this->lpa->donor->middlenames = 'efg';
        $this->lpa->donor->surname     = 'xyz';


        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => [],
                        'actor'                => $this->lpaData['actor'],
                    ]
                )
            )
        );
    }

    #[Then('/^I am told a new activation key is posted to the provided postcode$/')]
    public function iAmToldANewActivationIsPostedToTheProvidedPostcode(): void
    {
        $this->ui->assertPageAddress('/lpa/confirm-activation-key-generation');
    }

    #[Then('/^I can see the trust corporation (.*) in the list of attorneys$/')]
    public function ICanSeeTheTrustCorporationInTheListOfAttorneys($companyName): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText('The attorneys');
        $this->ui->assertPageContainsText($companyName);
    }

    private function fillAddLpaPages(
        string $code,
        string $day,
        string $month,
        string $year,
        string $reference_number,
    ): void {
        $this->ui->fillField('activation_key', $code);
        $this->ui->pressButton('Continue');

        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');

        $this->ui->fillField('reference_number', $reference_number);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to view an LPA which has an inactive attorney named (.*)$/')]
    public function iRequestToViewAnLPAWhichHasAnInactiveAttorney($name): void
    {
        $this->ui->assertPageContainsText('View LPA summary');

        $this->mockApiGetLpaByIdAndGetImagesById();
    }

    #[Then('/^I will not see (.*) in the attorney section of LPA summary$/')]
    public function iWillNotSeeInactiveAttorneyInTheListOfAttorneys($name): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText('The attorneys');
        $this->ui->assertPageNotContainsText($name);
    }

    #[When('/^I request to view an LPA with a donor who is also known as (.*)$/')]
    public function iRequestToViewAnLPAWithADonorWhoIsAlsoKnownAs($name): void
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->donor->otherNames = $name;

        $this->mockApiGetLpaByIdAndGetImagesById();
    }

    #[When('/^I request to view an LPA where all actors do not have an also known by name$/')]
    public function iRequestToViewAnWhereAllActorsDoNotHaveAnAlsoKnownByName(): void
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->donor->otherNames = null;

        foreach ($this->lpa->attorneys as $attorney) {
            $attorney->otherNames = null;
        }
        $this->mockApiGetLpaByIdAndGetImagesById();
    }

    public function mockApiGetLpaByIdAndGetImagesById(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // InstAndPrefImagesService::getImagesById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'uId'        => (int) $this->lpa->uId,
                        'status'     => 'COLLECTION_COMPLETE',
                        'signedUrls' => [],
                    ]
                ),
                self::INPSERVICE_GET_BY_ID
            )
        );

        $this->ui->clickLink('View LPA summary');
    }

    #[Then('/^I will see (.*) in the also known as field$/')]
    public function iWillSeeNameInTheAlsoKnownAsField($name): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText('Also known as');
        $this->ui->assertPageContainsText($name);
    }

    #[Then('/^I will not see the also known as field$/')]
    public function iWillNotSeeTheAlsoKnownAsField(): void
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageNotContainsText('Also known as');
    }

    #[Given('/^A system message is set$/')]
    public function aSystemMessageIsSet(): void
    {
        $this->systemMessageData = [
          'use/en'  => 'System Message Use English',
          'use/cy'  => 'System Message Use Welsh',
          'view/en' => 'System Message View English',
          'view/cy' => 'System Message View Welsh',
        ];
    }

    #[Given('/^A system message is not set$/')]
    public function aSystemMessageIsNotSet(): void
    {
        $this->systemMessageData = [];
    }

    #[Given('/^I am on the add an LPA reference number page$/')]
    public function iAmOnTheAddLPAReferenceNumberPage(): void
    {
        $this->ui->visit('/lpa/add-by-key/activation-key');
        $this->ui->assertPageAddress('/lpa/add-by-key/activation-key');

        $this->ui->fillField('activation_key', 'T3STPA22C0D3');
        $this->ui->pressButton('Continue');

        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');

        $this->ui->pressButton('Continue');

        $this->ui->assertPageAddress('/lpa/add-by-key/lpa-reference-number');
    }

    #[When('/^I click the Cancel link$/')]
    public function iClickTheCancelLink(): void
    {
        $this->ui->clickLink('Cancel');
    }

    #[When('/^I click Back to your LPAs$/')]
    public function iClickBackToYourLPAsLink(): void
    {
        $this->ui->clickLink('Back to your LPAs');
    }

    #[When('/^I return to the dashboard$/')]
    public function iReturnToTheDashboard(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Given('I have added a Combined LPA to my account')]
    public function iHaveAddedACombinedLpaToMyAccount(): void
    {
        $this->iHaveBeenGivenAccessToUseACombinedLPAViaCredentials();

        $this->dashboardLPAs = [$this->userLpaActorToken => $this->lpaData];
    }

    #[Given('I have been given access to use a Combined LPA via credentials')]
    public function iHaveBeenGivenAccessToUseACombinedLPAViaCredentials(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/combined_lpa.json'));

        $this->userLpaActorToken = '987654321';
        $this->actorId           = 9;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date'                 => new DateTimeImmutable('now'),
            'actor'                => [
                'type'    => 'primary-attorney',
                'details' => $this->lpa->attorneys[0],
            ],
            'lpa'                  => $this->lpa,
            'added'                => '2021-10-5 12:00:00',
        ];
    }
}
