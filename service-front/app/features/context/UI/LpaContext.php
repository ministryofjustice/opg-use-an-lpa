<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Message\RequestInterface;

/**
 * @property mixed  lpa
 * @property string userLpaActorToken
 * @property int    actorId
 * @property array  lpaData
 * @property string organisation
 * @property string accessCode
 * @property string userFirstName
 * @property int    userId
 * @property string userSurname
 * @property string activationCode
 * @property string codeCreatedDate
 */
class LpaContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;


    /**
     * @Then /^I receive an email confirming activation key request$/
     */
    public function iReceiveAnEmailConfirmingActivationKeyRequest()
    {
        //Not needed for this context

    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');

        $this->ui->assertElementContainsText('h1', 'We\'re sending you an activation key');
    }

    /**
     * @Given /^an attorney can be removed from acting on a particular LPA$/
     */
    public function anAttorneyCanBeRemovedFromActingOnAParticularLpa()
    {
        // Not needed for this context
    }

    /**
     * @Given I already have a valid activation key for my LPA
     */
    public function iAlreadyHaveAValidActivationKeyForMyLpa()
    {
        $this->activationCode = 'ACTVATIONCOD';
        $this->codeCreatedDate = (new DateTime())->modify('-15 days')->format('Y-m-d');
    }

    /**
     * @Then /^I am asked to check my answers before requesting an activation key$/
     */
    public function iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');
        $this->ui->assertPageContainsText('Check your answers');
        $this->ui->assertPageContainsText('700000000001');
        $this->ui->assertPageContainsText('The Attorney Person');
        $this->ui->assertPageContainsText('9 February 1998');
        $this->ui->assertPageContainsText('ABC123');
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
     * @Then I am informed that an LPA could not be found with these details
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');

        $this->ui->assertElementContainsText('h1', 'We could not find an LPA with the details you entered');
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
     * @Given /^I am on the request an activation key page$/
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheRequestAnActivationKeyPage()
    {
        $this->ui->visit('/lpa/add-by-paper');
        $this->ui->assertPageAddress('/lpa/add-by-paper');
    }

    /**
     * @Then /^I am shown a not found error$/
     */
    public function iAmShownANotFoundError()
    {
        $this->ui->assertResponseStatus(404);
    }

    /**
     * @Then /^I am taken back to previous page where I can see my answers and change them$/
     */
    public function iAmTakenBackToPreviousPageWhereICanSeeMyAnswersAndChangeThem()
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->assertFieldContains('opg_reference_number', '700000000001');
        $this->ui->assertFieldContains('first_names', 'The Attorney');
        $this->ui->assertFieldContains('last_name', 'Person');
        $this->ui->assertFieldContains('dob[day]', '09');
        $this->ui->assertFieldContains('dob[month]', '02');
        $this->ui->assertFieldContains('dob[year]', '1998');
        $this->ui->assertFieldContains('postcode', 'ABC123');
    }

    /**
     * @Then /^I am taken to page to ask for an activation key$/
     */
    public function iAmTakenToPageToAskForAnActivationKey()
    {
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('/lpa/add-by-paper');
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
     * @Then I am told that I cannot request an activation key
     */
    public function iAmToldThatICannotRequestAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');

        $this->ui->assertElementContainsText('h1', 'We cannot send an activation key for that LPA');

        $dashboardButton = $this->ui->getSession()->getPage()->findAll(
            'css',
            'main a.govuk-button[href^="/lpa/dashboard"]'
        );
        if (count($dashboardButton) !== 1) {
            throw new AssertionFailedError('Did not find button to navigate to dashboard');
        }

        $logoutButton = $this->ui->getSession()->getPage()->findAll('css', 'main a.govuk-button[href^="/logout"]');
        if (count($logoutButton) !== 1) {
            throw new AssertionFailedError('Did not find button to logout');
        }
    }

    /**
     * @Then I am told that I have an activation key for this LPA and where to find it
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLpaAndWhereToFindIt()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');

        $this->ui->assertElementContainsText('h1', 'You have an activation key for this LPA');
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

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
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
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
    }

    /**
     * @Then /^I can see the relevant (.*) and (.*) of my access codes and their details$/
     */
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle)
    {
        $this->ui->assertPageContainsText($activeTitle);
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');

        $this->ui->assertPageContainsText($inactiveTitle);
        $this->ui->assertPageContainsText('V - ABC3 - 21AB - CXYZ');
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

        $this->ui->assertPageContainsText('Access revoked');
        $this->ui->assertPageContainsText('You no longer have access to this LPA.');
    }

    /**
     * @Then /^I can see banner about existing LPAs$/
     */

    public function iCanSeeBannerAboutExistingLPAs()
    {
        $this->ui->assertElementOnPage(".moj-banner__message");
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
            'Expires' => $code1Expiry,
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        $code2 = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => $code2Expiry,
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
     * @When /^I confirm that those details are correct$/
     */
    public function iConfirmThatThoseDetailsAreCorrect()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');
        $this->ui->pressButton('Continue');
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
     * @Given I have been given access to use an LPA via credentials
     * @Given I have added an LPA to my account
     * @Given I have been given access to use an LPA via a paper document
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
        ];
    }

    /**
     * @Given /^I have created an access code$/
     */
    public function iHaveCreatedAnAccessCode()
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
        $this->iAmGivenAUniqueAccessCode();
    }

    /**
     * @Given /^I have generated an access code for an organisation and can see the details$/
     */
    public function iHaveGeneratedAnAccessCodeForAnOrganisationAndCanSeeTheDetails()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iClickToCheckMyAccessCodes();
        $this->iCanSeeAllOfMyAccessCodesAndTheirDetails();
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
     * @Given /^I have requested an activation key with valid details$/
     */
    public function iHaveRequestedAnActivationKeyWithValidDetails()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iRequestAnActivationKeyWithValidDetails();
        $this->iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey();
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
     * @When I provide details from an LPA registered before Sept 2019
     */
    public function iProvideDetailsFromAnLpaRegisteredBeforeSept2019()
    {
        $this->fillAndSubmitOlderLpaForm();

        // Setup fixture for success response
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'LPA not eligible due to registration date',
                            'details' => 'LPA not eligible due to registration date',
                            'data' => [],
                        ]
                    )
                )
            );
    }

    /**
     * @When I provide details that do not match a valid paper document
     */
    public function iProvideDetailsThatDoNotMatchAValidPaperDocument()
    {
        $this->fillAndSubmitOlderLpaForm();

        // Setup fixture for success response
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'LPA details do not match',
                            'details' => 'LPA details do not match',
                            'data' => [],
                        ]
                    )
                )
            );
    }

    /**
     * @When I provide the details from a valid paper document
     */
    public function iProvideTheDetailsFromAValidPaperDocument()
    {
        $this->fillAndSubmitOlderLpaForm();

        /**
         * This step definition needs to behave differently dependant on some prior context step
         */
        if ($this->activationCode === null) {
            // Setup fixture for success response
            $this->apiFixtures->patch('/v1/lpas/request-letter')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_NO_CONTENT,
                        [],
                        ''
                    )
                );
        } else {
            // Setup fixture for activation key already existing
            $this->apiFixtures->patch('/v1/lpas/request-letter')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_BAD_REQUEST,
                        [],
                        json_encode(
                            [
                                'title' => 'Bad request',
                                'details' => 'LPA not eligible as an activation key already exists',
                                'data' => [
                                    'activation_key_created' => $this->codeCreatedDate
                                ],
                            ]
                        )
                    )
                );
        }

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('personalisation', $params);
                }
            );
    }

    /**
     * @When /^I request an activation key with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidDOBFormatOf($day, $month, $year)
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->fillField('first_names', 'Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->fillField('postcode', 'ABC123');
        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with an invalid lpa reference number format of "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidLpaReferenceNumberFormatOf($referenceNumber)
    {
        $this->ui->fillField('opg_reference_number', $referenceNumber);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with valid details$/
     */
    public function iRequestAnActivationKeyWithValidDetails()
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->fillField('first_names', 'The Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->fillField('postcode', 'ABC123');
        $this->ui->fillField('dob[day]', '09');
        $this->ui->fillField('dob[month]', '02');
        $this->ui->fillField('dob[year]', '1998');
        $this->ui->pressButton('Continue');
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
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
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
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertEquals('XYUPHWQRECHV', $params['actor-code']);
                }
            );

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
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
     * @When /^I request to add an LPA with the code "([^"]*)" that is for "([^"]*)" "([^"]*)" and I will have an Id of ([^"]*)$/
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
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
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
        $this->apiFixtures->post('/v1/actor-codes/summary')
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

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
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
        $this->iAmOnTheDashboardPage();

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

        $this->ui->assertPageAddress('lpa/dashboard');
        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
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
     * @When /^I request to go back and change my answers$/
     */
    public function iRequestToGoBackAndChangeMyAnswers()
    {
        $this->ui->clickLink('Change');
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
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->ui->assertPageContainsText('View LPA summary');
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

        $this->ui->clickLink('View LPA summary');
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
     * @Given /^I requested an activation key within the last 14 days$/
     */
    public function iRequestedAnActivationKeyWithinTheLast14Days()
    {
        $this->codeCreatedDate = (new DateTime())->modify('-14 days')->format('Y-m-d');
    }

    /**
     * @When /^I say I do not have an activation key$/
     */
    public function iSayIDoNotHaveAnActivationKey()
    {
        $this->ui->fillField('activation_key_triage', 'No');
    }

    /**
     * @Then /^I see a page showing me the answers I have entered and content that helps me get it right$/
     */
    public function iSeeAPageShowingMeTheAnswersIHaveEnteredAndContentThatHelpsMeGetItRight()
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
        $this->ui->assertPageContainsText('LPA reference number: 700000000054');
        $this->ui->assertPageContainsText('Activation key: XYUPHWQRECHV');
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
        $this->apiFixtures->post('/v1/actor-codes/confirm')
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
        $this->apiFixtures->post('/v1/actor-codes/confirm')
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

    protected function fillAndSubmitOlderLpaForm()
    {
        $this->ui->fillField('opg_reference_number', $this->lpa->uId);
        $this->ui->fillField(
            'first_names',
            $this->lpa->donor->firstname . ' ' . $this->lpa->donor->middlenames
        );
        $this->ui->fillField('last_name', $this->lpa->donor->surname);

        $date = new DateTime($this->lpa->donor->dob);
        $this->ui->fillField('dob[day]', $date->format('d'));
        $this->ui->fillField('dob[month]', $date->format('m'));
        $this->ui->fillField('dob[year]', $date->format('Y'));

        $this->ui->fillField('postcode', ($this->lpa->donor->addresses[0])->postcode);

        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I will be told that I have already requested this and the date I should receive the letter by$/
     */
    public function iWillBeToldThatIHaveAlreadyRequestedThisAndTheDateIShouldReceiveTheLetterBy()
    {
        $this->ui->assertPageContainsText('You\'ve already asked for an activation key for this LPA');
        $expectedArrival = DateTime::createFromFormat(
            'Y-m-d',
            $this->codeCreatedDate
        )->modify('+2 weeks')->format('j F Y');
        $this->ui->assertPageContainsText($expectedArrival);
    }
}
