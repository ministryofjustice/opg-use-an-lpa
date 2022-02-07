<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\RequestHandler;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Message\RequestInterface;

/**
 * @property mixed  $lpa
 * @property string $userLpaActorToken
 * @property int    $actorId
 * @property array  $lpaData
 * @property string $organisation
 * @property string $accessCode
 * @property string $userFirstName
 * @property int    $userId
 * @property string $userSurname
 * @property string $activationCode
 * @property string $codeCreatedDate
 */
class LpaContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /** @var RequestHandler Allows the overriding of the dashboard LPA endpoints request (if registered) */
    private RequestHandler $requestDashboardLPAs;
    private RequestHandler $requestDashboardLPACodes;

    /**
     * @Then /^I am taken to a page explaining why instructions and preferences are not available$/
     */
    public function iAmTakenToAPageExplainingWhyInstructionsAndPreferencesAreNotAvailable()
    {
        $this->ui->assertPageContainsText('Preferences and instructions cannot be shown for this LPA');
    }

    /**
     * @Then /^I am taken to the change LPA details page$/
     */
    public function iAmTakenToTheChangeLPADetailsPage()
    {
        $this->ui->assertPageAddress('/lpa/change-lpa-details');
        $this->ui->assertPageContainsText('Let us know if something is incorrect on the LPA');
    }

    /**
     * @Then /^I am taken to the remove an LPA confirmation page for (.*) lpa$/
     */
    public function iAmTakenToTheRemoveAnLPAConfirmationPage($status)
    {
        $this->ui->assertPageAddress('/lpa/remove-lpa');
        $this->ui->assertPageContainsText('Are you sure you want to remove this LPA?');

        if ($status == 'Registered') {
            $this->ui->assertPageContainsText('LPA is registered');
        }
        if ($status == 'Cancelled') {
            $this->ui->assertPageNotContainsText(
                'you must contact us for a new activation key if you want to add the LPA back to your account'
            );
        }
    }

    /**
     * @Given /^I cannot see my LPA on the dashboard$/
     */
    public function iCannotSeeMyLPAOnTheDashboard()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageNotContainsText('Ian Deputy Deputy');
    }

    /**
     * @Given /^I can see a flash message confirming that my LPA has been removed$/
     */
    public function iCanSeeAFlashMessageConfirmingThatMyLPAHasBeenRemoved()
    {
        $this->ui->assertPageContainsText("You've removed Ian Deputy's health and welfare LPA");
    }

    /**
     * @When /^I click on the Read more link$/
     */
    public function iClickOnTheReadMoreLink()
    {
        $this->ui->clickLink('Read more');
    }

    /**
     * @When /^I request to remove an LPA from my account without the lpa actor token$/
     */
    public function iRequestToRemoveAnLPAFromMyAccountWithoutTheLpaActorToken()
    {
        $this->ui->visit('/lpa/remove-lpa');
    }

    /**
     * @When /^I select that I have seen something incorrect in the LPA details$/
     */
    public function iSelectThatIHaveSeenSomethingIncorrectInTheLPADetails()
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->clickLink('Seen something incorrect in the LPA details');
    }

    /**
     * @Given /^My active codes are cancelled$/
     */
    public function myActiveCodesAreCancelled()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The LPA is removed/
     */
    public function theLPAIsRemoved()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I confirm that I want to remove the LPA from my account$/
     */
    public function iConfirmThatIWantToRemoveTheLPAFromMyAccount()
    {
        // API call for removing an LPA from a users account
        $this->apiFixtures->delete('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                )
            );

        //API call for getting all the users added LPAs on the dashboard
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->pressButton('Yes, remove LPA');
    }

    /**
     * @Then /^I receive an email confirming activation key request$/
     */
    public function iReceiveAnEmailConfirmingActivationKeyRequest()
    {
        //Not needed for this context
    }

    /**
     * @Given /^an attorney can be removed from acting on a particular LPA$/
     */
    public function anAttorneyCanBeRemovedFromActingOnAParticularLpa()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $this->ui->assertPageAddress('/lpa/code-make');
        $this->ui->assertPageContainsText('XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('Give this access code to ' . $this->organisation);
    }

    /**
     * @Given /^I am inactive against the LPA on my account$/
     */
    public function iAmInactiveAgainstTheLpaOnMyAccount()
    {
        $this->lpaData['actor']['details']['systemStatus'] = false;
    }

    /**
     * @Then /^I am navigated to the instructions and preferences page$/
     */
    public function iAmNavigatedToTheInstructionsAndPreferencesPage()
    {
        $this->ui->assertPageAddress('/lpa/instructions-preferences');
        $this->ui->assertPageContainsText('Instructions and preferences');
    }

    /**
     * @Given /^I am on the activation key information page$/
     */
    public function iAmOnTheActivationKeyInformationPage()
    {
        $this->ui->visit('/lpa/add-by-paper-information');
        if (($this->base->container->get('Common\Service\Features\FeatureEnabled'))('allow_older_lpas')) {
            $this->ui->assertPageContainsText('Before you begin');
        } else {
            $this->ui->assertPageContainsText('Check if you can ask for an activation key');
        }
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        $this->ui->visit('/lpa/add-by-code');
        $this->ui->assertPageAddress('/lpa/add-by-code');
    }

    /**
     * @Given /^I am on the add an LPA triage page$/
     */
    public function iAmOnTheAddAnLPATriagePage()
    {
        $this->ui->visit('/lpa/add');
        $this->iAmTakenToTheAddAnLPATriagePage();
    }

    /**
     * @Then /^I am taken to the add an LPA triage page$/
     */
    public function iAmTakenToTheAddAnLPATriagePage()
    {
        $this->ui->assertPageAddress('/lpa/add');
        $this->ui->assertPageContainsText('Do you have an activation key to add an LPA?');
    }

    /**
     * @Given /^I am on the change details page$/
     */
    public function iAmOnTheChangeDetailsPage()
    {
        $this->ui->visit('/lpa/change-details');
        $this->ui->assertPageAddress('/lpa/change-details');
    }

    /**
     * @Given /^I am on the check LPA page$/
     */
    public function iAmOnTheCheckLPAPage()
    {
        $this->ui->assertPageAddress('/lpa/check');
    }

    /**
     * @Given /^I am on the dashboard page$/
     */
    public function iAmOnTheDashboardPage()
    {
        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Given /^I am on the death notification page$/
     */
    public function iAmOnTheDeathNotificationPage()
    {
        $this->ui->visit('/lpa/death-notification');
    }

    /**
     * @Given /^I am on the full lpa page$/
     */
    public function iAmOnTheFullLpaPage()
    {
        $this->iAmOnTheDashboardPage();
        $this->iRequestToViewAnLPAWhichStatusIs('Registered');
        $this->theFullLPAIsDisplayedWithTheCorrect('This LPA is registered');
    }

    /**
     * @When /^I am on the instructions and preferences page$/
     */
    public function iAmOnTheInstructionsAndPreferencesPage()
    {
        $this->iAmOnTheDashboardPage();
        $this->iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage('Read more');
        $this->iAmNavigatedToTheInstructionsAndPreferencesPage();
    }

    /**
     * @Then /^I am shown a not found error$/
     */
    public function iAmShownANotFoundError()
    {
        $this->ui->assertResponseStatus(404);
    }

    /**
     * @Then /^I am taken to page giving me information about asking for an activation key$/
     */
    public function iAmTakenToPageToAskForAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper-information');
    }

    /**
     * @Then /^I am taken to request an activation key form$/
     */
    public function iAmTakenToRequestAnActivationKeyForm()
    {
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('lpa/request-code/lpa-reference-number');
        $this->ui->assertPageContainsText('What is the LPA reference number?');
    }

    /**
     * @Then /^I am taken to the change details page$/
     */
    public function iAmTakenToTheChangeDetailsPage()
    {
        $this->ui->assertPageAddress('lpa/change-details?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('Let us know if a donor or attorney\'s details change');
    }

    /**
     * @Given /^I am the donor$/
     */
    public function iAmTheDonor()
    {
        $this->lpaData['actor']['type'] = 'donor';
        unset($this->lpaData['actor']['details']['systemStatus']);
    }

    /**
     * @Then /^I am told that I have 2 LPAs in my account$/
     */
    public function iAmToldThatIHave2LPAsInMyAccount()
    {
        $this->ui->assertPageContainsText('You have 2 LPAs in your account');
    }

    /**
     * @Given /^I have added an additional LPA to my account$/
     */
    public function iHaveAdded2LPAsToMyAccount()
    {
        $lpas = [];

        for ($x = 0; $x < 2; $x++) {
            //API call for getting each LPAs share codes
            $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode([])
                    )
                );

            // change the token within the LPA data to match as it changes
            $this->lpaData['user-lpa-actor-token'] = $this->userLpaActorToken;
            $lpas[$this->userLpaActorToken] = $this->lpaData;
            $this->userLpaActorToken = (string)(intval($this->userLpaActorToken) + 1);
        }

        //API call for getting all the users added LPAs
        $this->requestDashboardLPAs->respondWith(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($lpas)
            )
        );
    }

    /**
     * @Then /^I am told that my input is invalid because (.*)$/
     */
    public function iAmToldThatMyInputIsInvalidBecause($reason)
    {
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        $this->iAmOnTheAddAnLPAPage();

        // API call for checking add LPA data
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'Bad Request',
                            'details' => 'LPA already added',
                            'data' => [
                                'donor'         => [
                                    'uId'           => $this->lpa->donor->uId,
                                    'firstname'     => $this->lpa->donor->firstname,
                                    'middlenames'   => $this->lpa->donor->middlenames,
                                    'surname'       => $this->lpa->donor->surname,
                                ],
                                'caseSubtype' => $this->lpa->caseSubtype,
                                'lpaActorToken' => $this->userLpaActorToken
                            ],
                        ]
                    )
                )
            );

        $this->ui->fillField('passcode', 'XYUPHWQRECHV');
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I can find out why this LPA has been removed from the account$/
     */
    public function iCanFindOutWhyThisLPAHasBeenRemovedFromTheAccount()
    {
        $this->ui->clickLink('Why is this?');
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->assertPageContainsText('We\'ve removed an LPA from your account');
    }

    /**
     * @Then /^I can go back to the dashboard page$/
     */
    public function iCanGoBackToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->clickLink('Back');
        $this->ui->assertPageAddress('lpa/dashboard');
    }

    /**
     * @Given /^I can see a flash message for the added LPA$/
     */
    public function iCanSeeAFlashMessageForTheAddedLPA()
    {
        $this->ui->assertPageContainsText("You've added Ian Deputy's health and welfare LPA");
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
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

    /**
     * @Then /^I can see the relevant (.*) and (.*) of my access codes and their details$/
     */
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle)
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

    /**
     * @Then /^I can see authority to use the LPA is revoked$/
     */
    public function iCanSeeAuthorityToUseTheLpaIsRevoked()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        $code = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => '2024-01-01T23:59:59+00:00',
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([0 => $code]))
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText('You are not an attorney on this LPA anymore.');
    }

    /**
     * @Then /^I can see banner about existing LPAs before 2019$/
     */

    public function iCanSeeBannerAboutExistingLPAsBefore2019()
    {
        $this->ui->assertElementOnPage('.moj-banner__message');
        $this->ui->assertElementContainsText('.moj-banner__message', '2019');
    }

    /**
     * @Then /^I can see banner about existing LPAs after 2016$/
     */

    public function iCanSeeBannerAboutExistingLPAsAfter2016()
    {
        $this->ui->assertElementOnPage('.moj-banner__message');
        $this->ui->assertElementContainsText('.moj-banner__message', '2016');
    }


    /**
     * @Then /^I can see (.*) link along with the instructions or preference message$/
     */
    public function iCanSeeReadMoreLink($readMoreLink)
    {
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText('Important: This LPA has instructions or preferences');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $readMoreLink = $page->findLink($readMoreLink);
        if ($readMoreLink === null) {
            throw new Exception($readMoreLink . ' link not found');
        }
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        $code1 = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => (new DateTime())->modify($code1Expiry)->format('Y-m-d'),
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        $code2 = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => (new DateTime())->modify($code2Expiry)->format('Y-m-d'),
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => $code1,
                            1 => $code2,
                        ]
                    )
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText($noActiveCodes);
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('No organisations have access');
    }

    /**
     * @Then /^I can see the code has not been used to view the LPA$/
     */
    public function iCanSeeTheCodeHasNotBeenUsedToViewTheLPA()
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('Not viewed');
    }

    /**
     * @Then /^I can see the message (.*)$/
     * <Important: This LPA has instructions or preferences>
     */
    public function iCanSeeTheMessage($message)
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText($message);
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA()
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('Natwest');
        $this->ui->assertPageContainsText('Another Organisation');
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->pressButton("Cancel organisation's access");

        $this->iWantToBeAskedForConfirmationPriorToCancellation();
    }

    /**
     * @When /^I cancel the viewer code/
     */
    public function iCancelTheViewerCode()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-09-16T22:57:12.398570Z',
                                'Organisation' => $this->organisation,
                                'UserLpaActor' => $this->userLpaActorToken,
                                'ViewerCode' => $this->accessCode,
                                'Cancelled' => '2020-09-16T22:58:43+00:00',
                                'Expires' => '2020-09-16T23:59:59+01:00',
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );
    }

    /**
     * @Then /^I cannot check existing or inactive access codes for the LPA$/
     */
    public function iCannotCheckExistingOrInactiveAccessCodesForTheLpa()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/access-codes"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/access-codes');
        }
    }

    /**
     * @Then /^I cannot make access codes for the LPA$/
     */
    public function iCannotMakeAccessCodesForTheLpa()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/code-make"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/code-make');
        }
    }

    /**
     * @Then /^I cannot view the LPA summary$/
     */
    public function iCannotViewTheLpaSummary()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/view-lpa"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/view-lpa');
        }
    }

    /**
     * @When /^I check my access codes/
     */
    public function iCheckMyAccessCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click the Continue link$/
     */
    public function iClickTheContinueLink()
    {
        $this->ui->clickLink('Continue');
    }

    /**
     * @When /^I click the (.*) link in the instructions or preference message$/
     */
    public function iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage($readMoreLink)
    {
        $this->iCanSeeReadMoreLink($readMoreLink);
        $this->ui->clickLink($readMoreLink);
    }

    /**
     * @When /^I click the (.*) to change a donor or attorneys details$/
     */
    public function iClickTheToChangeADonorOrAttorneysDetails($link)
    {
        $this->ui->assertPageAddress('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
        $this->ui->clickLink($link);
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check my access codes$/
     */
    public function iClickToCheckMyAccessCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => (new DateTime('yesterday'))->format('c'),
                                'Expires' => (new DateTime('+1 month'))->setTime(23, 59, 59)->format('c'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA$/
     */
    public function iClickToCheckMyAccessCodesThatIsUsedToViewLPA()
    {
        $organisation = 'Natwest';

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => [
                                    0 => [
                                        'Viewed' => '2020-10-01T15:27:23.263483Z',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => $organisation,
                                    ],
                                    1 => [
                                        'Viewed' => '2020-10-01T15:27:23.263483Z',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => 'Another Organisation',
                                    ],
                                ],
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check my active and inactive codes$/
     */
    public function iClickToCheckMyActiveAndInactiveCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                            1 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => "ABC321ABCXYZ",
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check the viewer code has been cancelled which is now expired/
     */
    public function iClickToCheckTheViewerCodeHasBeenCancelledWhichIsNowExpired()
    {
        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call to cancel code
        $this->apiFixtures->put('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Organisation' => $this->organisation,
                                'UserLpaActor' => $this->userLpaActorToken,
                                'ViewerCode' => $this->accessCode,
                                'Cancelled' => '2021-01-02T23:59:59+00:00',
                                'Expires' => '2021-01-02T23:59:59+00:00',
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->pressButton("Yes, cancel code");
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code$/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Organisation' => $this->organisation,
                                'UserLpaActor' => $this->userLpaActorToken,
                                'ViewerCode' => $this->accessCode,
                                'Expires' => '2021-01-05T23:59:59+00:00',
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $this->ui->pressButton("No, return to access codes");
    }

    /**
     * @When /^I do not select an option for whether I have an activation key$/
     */
    public function iDoNotSelectAnOptionForWhetherIHaveAnActivationKey()
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I expect to be on the death notification page$/
     */
    public function iExpectToBeOnTheDeathNotificationPage()
    {
        $this->ui->assertPageAddress('/lpa/death-notification');
    }

    /**
     * @When /^I fill in the form and click the cancel button$/
     */
    public function iFillInTheFormAndClickTheCancelButton()
    {
        // API call for finding all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->clickLink('Cancel');
    }

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        // Not needed for one this context
    }

    /**
     * @Given /^I have added a (.*) LPA$/
     */
    public function iHaveAddedALPA($lpaType)
    {
        // Dashboard page

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Given I have added an LPA to my account
     */
    public function iHaveAddedAnLpaToMyAccount()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        //API call for getting all the users added LPAs
        $this->requestDashboardLPAs = $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->requestDashboardLPACodes = $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );
    }

    /**
     * @Given I have no LPAs in my account
     */
    public function iHaveNoLpasInMyAccount()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );
    }

    /**
     * @Given I have been given access to use an LPA via credentials
     *
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json'));

        $this->userLpaActorToken = '987654321';
        $this->actorId = 9;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date' => 'today',
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'addresses' => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country' => '',
                            'county' => '',
                            'id' => 0,
                            'postcode' => '',
                            'town' => '',
                            'type' => 'Primary',
                        ],
                    ],
                    'companyName' => null,
                    'dob' => '1975-10-05',
                    'email' => 'string',
                    'firstname' => 'Ian',
                    'id' => 0,
                    'middlenames' => null,
                    'salutation' => 'Mr',
                    'surname' => 'Deputy',
                    'systemStatus' => true,
                    'uId' => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpa' => $this->lpa,
            'added' => '2021-10-5 12:00:00'
        ];
    }

    /**
     * @Given I have been given access to use an LPA via credentials which has a donor signature before 2016
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentialsSignedBefore2016()
    {
        $this->lpa = json_decode(file_get_contents(
            __DIR__ . '../../../../test/fixtures/full_example_signed_before_2016.json'
        ));

        $this->userLpaActorToken = '987654321';
        $this->actorId = 9;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date' => 'today',
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'addresses' => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country' => '',
                            'county' => '',
                            'id' => 0,
                            'postcode' => '',
                            'town' => '',
                            'type' => 'Primary',
                        ],
                    ],
                    'companyName' => null,
                    'dob' => '1975-10-05',
                    'email' => 'string',
                    'firstname' => 'Ian',
                    'id' => 0,
                    'middlenames' => null,
                    'salutation' => 'Mr',
                    'surname' => 'Deputy',
                    'systemStatus' => true,
                    'uId' => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpaDonorSignatureDate' => '2015-06-30',
            'lpa' => $this->lpa,
        ];

        //API call for getting all the users added LPAs
        $this->requestDashboardLPAs = $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->requestDashboardLPACodes = $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );
    }

    /**
     * @Given I have added an LPA to my account which has a donor signature before 2016
     */
    public function iHaveAddedAnLPAToMyAccountSignedBefore2016()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentialsSignedBefore2016();

        //API call for getting all the users added LPAs
        $this->requestDashboardLPAs = $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->requestDashboardLPACodes = $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );
    }

    /**
     * @Given /^I have created an access code$/
     */
    public function iHaveCreatedAnAccessCode()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";
    }

    /**
     * @Given /^I have generated an access code for an organisation and can see the details$/
     */
    public function iHaveGeneratedAnAccessCodeForAnOrganisationAndCanSeeTheDetails()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iAmOnTheDashboardPage();
        $this->iClickToCheckMyAccessCodes();
        $this->iCanSeeAllOfMyAccessCodesAndTheirDetails();
    }

    /**
     * @Given I have generated an access code for an LPA on my account
     */
    public function iHaveGeneratedAnAccessCodeForAnLPAOnMyAccount()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => (new DateTime('yesterday'))->format('c'),
                                'Expires' => (new DateTime('+1 month'))->setTime(23, 59, 59)->format('c'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );
    }

    /**
     * @When /^I have not provided required information for creating access code such as (.*)$/
     */
    public function iHaveNotProvidedRequiredInformationForCreatingAccessCodeSuchAs($organisationname)
    {
        $this->ui->assertPageContainsText("Which organisation do you want to give access to?");

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->fillField('org_name', $organisationname);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I navigate to check an access code$/
     */
    public function iNavigateToCheckAnAccessCode()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('lpa/access-codes?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I navigate to give an organisation access$/
     */
    public function iNavigateToGiveAnOrganisationAccess()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I navigate to view the LPA summary$/
     */
    public function iNavigateToViewTheLpaSummary()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I request an activation key without entering my (.*)$/
     */
    public function iRequestAnActivationKeyWithoutEnteringMy($data)
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode(
                        [
                            'title' => 'Not found',
                            'details' => 'Code validation failed',
                            'data' => [],
                        ]
                    )
                )
            );

        $this->ui->fillField('passcode', 'ABC321GHI567');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA whose status is (.*) using (.*)$/
     */
    public function iRequestToAddAnLPAWhoseStatusIs(string $status, string $code)
    {
        $this->lpa->status = $status;

        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'Bad Request',
                            'details' => 'LPA status is not registered',
                            'data' => [],
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertEquals('XYUPHWQRECHV', $params['actor-code']);
                }
            );

        $this->ui->fillField('passcode', $code);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidDOBFormatOf1($day, $month, $year)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with an invalid passcode format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidPasscodeFormatOf1($passcode)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', $passcode);
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with an invalid reference number format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidReferenceNumberFormatOf($referenceNo)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', $referenceNo);
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with the code "([^"]*)" that is for "([^"]*)" "([^"]*)" \
     *       and I will have an Id of ([^"]*)$/
     */
    public function iRequestToAddAnLPAWithTheCodeThatIsForAndIWillHaveAnIdOf(
        $passcode,
        $firstName,
        $secondName,
        $id
    ) {
        $this->userId = $this->actorId = (int)$id;

        $this->userFirstName = $firstName;
        $this->userSurname = $secondName;

        // API Response for LPA data request, configured with our specified details
        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date' => 'today',
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'addresses' => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country' => '',
                            'county' => '',
                            'id' => 0,
                            'postcode' => '',
                            'town' => '',
                            'type' => 'Primary',
                        ],
                    ],
                    'companyName' => null,
                    'id' => $this->actorId,
                    'uId' => '700000000054',
                    'dob' => '1975-10-05',
                    'salutation' => 'Mr',
                    'firstname' => $this->userFirstName,
                    'middlenames' => null,
                    'surname' => $this->userSurname,
                    'systemStatus' => true,
                    'email' => 'string',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpa' => $this->lpa,
        ];

        // API call for checking LPA
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        $this->ui->fillField('passcode', $passcode);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with valid details using (.*) which matches (.*)$/
     */
    public function iRequestToAddAnLPAWithValidDetailsUsing(string $code, string $storedCode)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) use ($storedCode) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertEquals($storedCode, $params['actor-code']);
                }
            );

        $this->ui->fillField('passcode', $code);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to give an organisation access$/
     */
    public function iRequestToGiveAnOrganisationAccess()
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('/lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I request to give an organisation access for my (.*) LPA$/
     */
    public function iRequestToGiveAnOrganisationAccessForMyLPA($lpaType)
    {
        $this->lpa->caseSubtype = $lpaType;

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Give an organisation access');
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call for get LpaById (when give organisation access is clicked)
        $this->iRequestToGiveAnOrganisationAccess();

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'code' => $this->accessCode,
                            'expires' => '2021-03-07T23:59:59+00:00',
                            'organisation' => $this->organisation,
                        ]
                    )
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->fillField('org_name', $this->organisation);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        $this->ui->pressButton('Try again');
        $this->ui->assertPageAddress('/lpa/add');
    }

    /**
     * @When /^I request to remove an LPA from my account that is (.*)$/
     */
    public function iRequestToRemoveAnLPAFromMyAccountThatIs($status)
    {
        $this->lpa->status = $status;

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );
        $this->ui->clickLink('Remove LPA');
        $this->ui->assertPageAddress('/lpa/remove-lpa');
    }

    /**
     * @When /^I request to view an LPA which has a donor signature before 2016$/
     */
    public function iRequestToViewAnLPAWhichHasADonorSignatureBefore2016()
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );
        $this->ui->clickLink('View LPA summary');
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->status = $status;

        if ($status === 'Revoked') {
            // API call for get LpaById
            $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'user-lpa-actor-token' => $this->userLpaActorToken,
                                'date' => 'date',
                                'lpa' => [],
                                'actor' => $this->lpaData['actor'],
                            ]
                        )
                    )
                );
        } else {
            // API call for get LpaById
            $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'user-lpa-actor-token' => $this->userLpaActorToken,
                                'date' => 'date',
                                'lpa' => $this->lpa,
                                'actor' => $this->lpaData['actor'],
                            ]
                        )
                    )
                );
            $this->ui->clickLink('View LPA summary');
        }
    }

    /**
     * @When /^I request to view the LPA that has already been added$/
     */
    public function iRequestToViewTheLPAThatHasAlreadyBeenAdded()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->clickLink('see this LPA');
    }

    /**
     * @When /^I say I do not have an activation key$/
     */
    public function iSayIDoNotHaveAnActivationKey()
    {
        $this->ui->fillField('activation_key_triage', 'No');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I see a page showing me the answers I have entered and content that helps me get it right$/
     */
    public function iSeeAPageShowingMeTheAnswersIHaveEnteredAndContentThatHelpsMeGetItRight()
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
        $this->ui->assertPageContainsText('LPA reference number: 700000000054');
        $this->ui->assertPageContainsText('Activation key: C-XYUPHWQRECHV');
        $this->ui->assertPageContainsText('Date of birth: 5 October 1975');
    }

    /**
     * @When /^I select to add an LPA$/
     */
    public function iSelectToAddAnLPA()
    {
        $this->ui->clickLink('Add another LPA');
    }

    /**
     * @When /^I choose to add my first LPA$/
     */
    public function iChooseToAddMyFirstLPA()
    {
        $this->ui->clickLink('Add your first LPA');
    }

    /**
     * @When /^I select to find out more if the donor or an attorney dies$/
     */
    public function iSelectToFindOutMoreIfTheDonorOrAnAttorneyDies()
    {
        $this->ui->clickLink('the donor or an attorney dies');
    }

    /**
     * @When /^I select (.*) whether I have an activation key$/
     */
    public function iSelectWhetherIHaveAnActivationKey($option)
    {
        $this->ui->fillField('activation_key_triage', $option);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I should be able to click a link to go and create the access codes$/
     */
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes()
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('Which organisation do you want to give access to');
    }

    /**
     * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('Inactive codes');
        $this->ui->assertPageContainsText("V - XYZ3 - 21AB - C987");
        $this->ui->assertPageContainsText('Cancelled');
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status (.*)/
     */
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus($status)
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $codeDetails = [];

        $codeSummary = $page->findAll('css', '.govuk-summary-list__row');
        foreach ($codeSummary as $codeItem) {
            $codeDetails[] = ($codeItem->find('css', 'dd'))->getText();
        }

        assertEquals($codeDetails[0], 'V - XYZ3 - 21AB - C987');
        assertEquals($codeDetails[1], 'Ian Deputy');
        assertEquals($codeDetails[2], 'Not viewed');
        assertEquals($codeDetails[4], $status);

        if ($codeDetails === null) {
            throw new Exception('Code details not found');
        }
    }

    /**
     * @Then /^I should be taken back to the access code summary page/
     */
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage()
    {
        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText("V - XYZ3 - 21AB - C987");
        $this->ui->assertPageNotContainsText('Cancelled');
    }

    /**
     * @Then /^I should be told access code could not be created due to (.*)$/
     */
    public function iShouldBeToldAccessCodeCouldNotBeCreatedDueTo($reasons)
    {
        $this->ui->assertPageAddress('/lpa/code-make');

        $this->ui->assertPageContainsText($reasons);
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA()
    {
        $this->ui->assertPageContainsText("You've already added this LPA to your account");
    }

    /**
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet()
    {
        $this->ui->assertPageContainsText('Check access codes');
        $this->ui->assertPageContainsText('There are no access codes for this LPA');
        $this->ui->assertPageContainsText('Give an organisation access');
    }

    /**
     * @Given /^I should not see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldNotSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
    {
        $this->ui->assertPageNotContainsText(
            sprintf(
                'You cancelled the access code for %s: V-XYZ3-21AB-C987',
                $this->organisation
            )
        );
    }

    /**
     * @Given /^I should see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
    {
        $this->ui->assertPageContainsText(
            sprintf(
                'You cancelled the access code for %s: V-XYZ3-21AB-C987',
                $this->organisation
            )
        );
    }

    /**
     * @Then /^I should see relevant (.*) of organisations$/
     */
    public function iShouldSeeRelevantOfOrganisations($orgDescription)
    {
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText($orgDescription);
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->ui->assertPageContainsText("Are you sure you want to cancel this code?");
    }

    /**
     * @When /^I want to cancel the access code for an organisation$/
     */
    public function iWantToCancelTheAccessCodeForAnOrganisation()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I want to see the option to cancel the code$/
     */
    public function iWantToSeeTheOptionToCancelTheCode()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText("Cancel organisation's access");
    }

    /**
     * @Then /^I will be taken to the appropriate (.*) to add an lpa$/
     */
    public function iWillBeTakenToTheAppropriateToAddAnLpa($page)
    {
        $this->ui->assertPageContainsText($page);
    }

    /**
     * @Then /^I will be told that I must select whether I have an activation key$/
     */
    public function iWillBeToldThatIMustSelectWhetherIHaveAnActivationKey()
    {
        $this->ui->assertPageContainsText('Select if you have an activation key to add the LPA');
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // API call for adding an LPA
        $this->apiFixtures->post('/v1/add-lpa/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userLpaActorToken])
                )
            );

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->assertPageAddress('/lpa/check');

        $this->ui->assertPageContainsText('Is this the LPA you want to add?');
        $this->ui->assertPageContainsText('Mrs Ian Deputy Deputy');

        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^The correct LPA is found and I can see the correct name which will have a role of "([^"]*)"$/
     */
    public function theCorrectLPAIsFoundAndICanSeeTheCorrectNameWhichWillHaveARoleOf($role)
    {
        // API call for adding an LPA
        $this->apiFixtures->post('/v1/add-lpa/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userLpaActorToken])
                )
            );

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->assertPageAddress('/lpa/check');

        $this->ui->assertPageContainsText('Is this the LPA you want to add?');
        $this->ui->assertPageContainsText(sprintf('Mr %s %s', $this->userFirstName, $this->userSurname));
        $this->ui->assertPageContainsText($role);

        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^The full LPA is displayed$/
     */
    public function theFullLPAIsDisplayed()
    {
        $this->ui->assertPageAddress('/lpa/view-lpa?=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('This LPA is registered');
    }

    /**
     * @Then /^The full LPA is displayed with the correct (.*)$/
     */
    public function theFullLPAIsDisplayedWithTheCorrect($message)
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText($message);
    }

    /**
     * @Then /^The Revoked LPA details are not displayed$/
     */
    public function theRevokedLPADetailsAreNotDisplayed()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageNotContainsText(
            $this->lpa->donor->firstname . '' .
            $this->lpa->donor->middlenames . ' ' .
            $this->lpa->donor->surname
        );
    }

    /**
     * @Given /^The LPA has not been added$/
     */
    public function theLPAHasNotBeenAdded()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('Add your first LPA');
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('Ian Deputy Deputy');
        $this->ui->assertPageContainsText('Health and welfare');
    }

    /**
     * @When /^I check access codes of the status changed LPA$/
     */
    public function iCheckAccessCodesOfTheStatusChangedLpa()
    {
        $this->lpa->status = "Revoked";

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => [],
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );
    }

    /**
     * @When /^The LPA has been revoked$/
     * @Then /^I cannot see my access codes and their details$/
     */
    public function theStatusOfTheLpaGotRevoked()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I request to give an organisation access to the LPA whose status changed to Revoked$/
     * @When /^I request to view an LPA whose status changed to Revoked$/
     */
    public function iRequestToGiveAnOrganisationAccessToTheLPAWhoseStatusChangedToRevoked()
    {
        $this->lpa->status = "Revoked";
        $this->lpa->donor->firstname = "abc";
        $this->lpa->donor->middlenames = "efg";
        $this->lpa->donor->surname = "xyz";


        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->userLpaActorToken,
                            'date' => 'date',
                            'lpa' => [],
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );
    }

    /**
     * @Then /^I am told a new activation key is posted to the provided postcode$/
     */
    public function iAmToldANewActivationIsPostedToTheProvidedPostcode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-activation-key-generation');
    }
}
