<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ViewerContextTrait;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Class ViewerContext
 *
 * @package BehatTest\Context\UI
 *
 * @property $lpaSurname
 * @property $lpaShareCode
 * @property $lpaData
 * @property $lpaStoredCode
 * @property $lpaViewedBy
 */
class ViewerContext implements Context
{
    use ViewerContextTrait;
    use BaseUiContextTrait;

    /**
     * @Then /^a PDF is downloaded$/
     */
    public function aPDFIsDownloaded()
    {
        // We can't actually check the content of the PDF but we can check that the response
        // we're given has the headers we want the PDF file to have.

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->assertResponseHeader('Content-Type', 'application/pdf');
        $this->assertResponseHeader(
            'Content-Disposition',
            'attachment; filename=lpa-' . $this->lpaData['uId'] . '.pdf'
        );
    }

    /**
     * @Given /^I access the viewer service$/
     * @Then /^I am taken to viewer service home page$/
     */
    public function iAccessTheViewerService()
    {
        $this->ui->visit('/home');
    }

    /**
     * @Given /^I am on the enter code page$/
     */
    public function iAmOnTheEnterCodePage()
    {
        $this->ui->visit('/home');
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @Given /^I am on the stats page$/
     */
    public function iAmOnTheStatsPage()
    {
        $this->ui->visit('/stats');
    }

    /**
     * @Given /^I am on the triage page$/
     */
    public function iAmOnTheTriagePage()
    {
        $this->ui->visit('/home');
    }

    /**
     * @Given /^I am on the viewer privacy notice page$/
     */
    public function iAmOnTheViewerPrivacyNoticePage()
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageContainsText('View a lasting power of attorney');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    /**
     * @Given /^I am on the viewer terms of use page$/
     */
    public function iAmOnTheViewerTermsOfUsePage()
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Given /^I am shown the LPA summary found with valid credentials$/
     */
    public function iAmShownTheLPASummaryFoundWithValidCredentials()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAValidLPAShareCode();
        $this->iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect();
        $this->iAmViewingAValidLPA();
    }

    /**
     * @Then /^I am taken back to the enter code page$/
     */
    public function iAmTakenBackToTheEnterCodePage()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Enter the LPA access code');
    }

    /**
     * @Then /^I am taken back to the terms of use page$/
     */
    public function iAmTakenBackToTheTermsOfUsePage()
    {
        $this->iAmOnTheViewerTermsOfUsePage();
    }

    /**
     * @Then /^I am taken to the session expired page$/
     */
    public function iAmTakenToTheSessionExpiredPage()
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText('You\'ll have to start again');
    }

    /**
     * @Then /^I am taken to the viewer cookies page$/
     */
    public function iAmTakenToTheViewerCookiesPage()
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText('View a lasting power of attorney service');
    }

    /**
     * @Then /^I am told that I must enter my organisation name$/
     */
    public function iAmToldThatIMustEnterMyOrganisationName()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Enter your organisation name');
    }

    /**
     * @Then /^I am told that I must enter the donor's last name$/
     */
    public function iAmToldThatIMustEnterTheDonorSLastName()
    {
        $this->ui->assertPageContainsText("Enter the donor's last name");
    }

    /**
     * @Then /^I am told that my input is invalid because (.*)$/
     */
    public function iAmToldThatMyInputIsInvalidBecause($reason)
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @Then /^I am told that the share code is invalid because (.*)$/
     */
    public function iAmToldThatTheShareCodeIsInvalidBecause($reason)
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @Given /^I am viewing a cancelled LPA$/
     * @Then /^I can see the full details of the cancelled LPA$/
     */
    public function iAmViewingACancelledLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );
        $this->ui->assertPageContainsText('LPA has been cancelled');
        $this->ui->assertPageContainsText(
            'Cancelled on ' . (new DateTime($this->lpaData['cancellationDate']))->format('j F Y')
        );
    }

    /**
     * @Given /^I am viewing a revoked LPA$/
     * @Then /^I can see the full details of the revoked LPA$/
     */
    public function iAmViewingARevokedLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );
        $this->ui->assertPageContainsText('LPA has been cancelled');
        $this->ui->assertPageNotContainsText(
            'Cancelled on'
        );
    }

    /**
     * @Given /^I am viewing a valid LPA$/
     * @Then /^I can see the full details of the valid LPA$/
     */
    public function iAmViewingAValidLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );
        $this->ui->assertPageContainsText('LPA is valid');
    }

    /**
     * @Given /^I attempted an invalid share codes$/
     */
    public function iAttemptedAnInvalidShareCodes()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAShareCodeThatHasExpired();
    }

    /**
     * @Then /^I can see the accessibility statement for the View service$/
     */
    public function iCanSeeTheAccessibilityStatementForTheViewService()
    {
        $this->ui->assertPageContainsText('Accessibility statement for View a lasting power of attorney');
    }

    /**
     * @Then /^I can see the viewer privacy notice$/
     */
    public function iCanSeeTheViewerPrivacyNotice()
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    /**
     * @Then /^I can see the viewer terms of use$/
     */
    public function iCanSeeTheViewerTermsOfUse()
    {
        $this->ui->assertPageAddress('/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
    }

    /**
     * @Then /^I can see user LPA codes table$/
     */
    public function iCanSeeUserLPACodesTable()
    {
        $this->ui->assertPageAddress('/stats');
        $this->ui->assertPageContainsText('Number of LPA codes viewed');
    }

    /**
     * @When /^I choose to download a document version of the LPA$/
     */
    public function iChooseToDownloadADocumentVersionOfTheLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], str_replace('-', '', $this->lpaShareCode));
                }
            );

        // API to pdf service for pdf rendering
        $this->apiFixtures->post('/generate-pdf')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], ''))
            ->inspectRequest(
                function (RequestInterface $request) {
                    assertStringStartsWith('<!DOCTYPE html>', $request->getBody()->getContents());
                }
            );

        $this->ui->clickLink('Download this LPA summary');
    }

    /**
     * @When /^I click the (.*) link on the page$/
     */
    public function iClickTheBackLinkOnThePage($backLink)
    {
        $this->ui->assertPageContainsText($backLink);
        $this->ui->clickLink($backLink);
    }

    /**
     * @When /^I confirm the cancelled LPA is correct/
     */
    public function iConfirmTheCancelledLPAIsCorrect()
    {
        $this->lpaData['status'] = 'Cancelled';
        $this->lpaData['cancellationDate'] = (new DateTime('-1 day'))->format('Y-m-d');

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                            'cancelled' => (new DateTime('-1 day'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                    assertEquals($params['organisation'], $this->lpaViewedBy);
                }
            );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');
    }

    /**
     * @When /^I confirm the revoked LPA is correct/
     */
    public function iConfirmTheRevokedLPAIsCorrect()
    {
        $this->lpaData['status'] = 'Revoked';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                            'cancelled' => (new DateTime('-1 day'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                    assertEquals($params['organisation'], $this->lpaViewedBy);
                }
            );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');
    }

    /**
     * @When /^I enter an organisation name and confirm the LPA is correct$/
     */
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect()
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Ask the donor or attorney for a new access code if your organisation:');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                    assertEquals($params['organisation'], $this->lpaViewedBy);
                }
            );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');
    }

    /**
     * @When /^I give a share code that has expired$/
     */
    public function iGiveAShareCodeThatHasExpired()
    {
        $this->lpaData['status'] = 'Expired';
        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_GONE,
                    [],
                    json_encode(
                        [
                            'title' => 'Gone',
                            'details' => 'Share code expired',
                            'data' => [],
                        ]
                    )
                )
            );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I give a share code that's been cancelled$/
     */
    public function iGiveAShareCodeThatsBeenCancelled()
    {
        $this->lpaData['status'] = 'Cancelled';
        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_GONE,
                    [],
                    json_encode(
                        [
                            'title' => 'Gone',
                            'details' => 'Share code cancelled',
                            'data' => [],
                        ]
                    ),
                    '1.1',
                    'Share code cancelled'
                )
            );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I give a valid LPA share code on a cancelled LPA$/
     */
    public function iGiveAValidCancelledLPAShareCode()
    {
        $this->lpaData['status'] = 'Cancelled';

        $this->ui->assertPageAddress('/home');

        // API call for lpa summary check
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                }
            );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I give a valid LPA share code$/
     */
    public function iGiveAValidLPAShareCode()
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/home');

        // API call for lpa summary check
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                }
            );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I give a valid LPA share code of (.*) which matches (.*)$/
     */
    public function iGiveAValidLPAShareCodeOf(string $code, string $storedCode)
    {
        $this->lpaShareCode = $code;
        $this->lpaStoredCode = $storedCode;
        $this->iGiveAValidLPAShareCode();
    }

    /**
     * @When /^I give an invalid (.*) and (.*)$/
     */
    public function iGiveAnInvalidShareCodeAndSurname($shareCode, $surname)
    {
        $this->ui->assertPageAddress('/home');

        // API call for lpa summary check
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->ui->fillField('donor_surname', $surname);
        $this->ui->fillField('lpa_code', $shareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I have an error message informing me to try again\.$/
     */
    public function iHaveAnErrorMessageInformingMeToTryAgain()
    {
        $this->iAmTakenBackToTheEnterCodePage();
        $this->ui->assertPageContainsText(
            'As you have not used this service for over 20 minutes, the page has timed out. We\'ve now ' .
                'refreshed the page - please try to sign in again'
        );
    }

    /**
     * @Given /^I have been given access to a cancelled LPA via share code$/
     */
    public function iHaveBeenGivenAccessToACancelledLPAViaShareCode()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Cancelled';
    }

    /**
     * @Given /^I have been given access to an expired LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnExpiredLPAViaShareCode()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Expired';
    }

    /**
     * @Given /^I have been given access to an LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnLPAViaShareCode()
    {
        $this->lpaSurname = 'Testerson';
        $this->lpaShareCode = '1111-1111-1111';
        $this->lpaStoredCode = '111111111111';
        $this->lpaViewedBy = 'Santander';
        $this->lpaData = [
            'id' => 1,
            'uId' => '7000-0000-0000',
            'receiptDate' => '2014-09-26',
            'registrationDate' => '2014-10-26',
            'lpaDonorSignatureDate' => '2015-06-30',
            'donor' => [
                'id' => 1,
                'uId' => '7000-0000-0288',
                'dob' => '1948-11-01',
                'salutation' => 'Mr',
                'firstname' => 'Test',
                'middlenames' => 'Testable',
                'surname' => 'Testerson',
                'addresses' => [
                    0 => [
                        'id' => 1,
                        'town' => 'Test',
                        'county' => 'Testershire',
                        'postcode' => 'TE57 7ES',
                        'country' => '',
                        'type' => 'Primary',
                        'addressLine1' => 'Test House',
                        'addressLine2' => 'Test Road',
                        'addressLine3' => '',
                    ],
                ],
            ],
            'status' => 'Registered',
            'caseSubtype' => 'hw',
        ];
    }

    /**
     * @When /^I leave the organisation name blank and confirm the LPA is correct$/
     */
    public function iLeaveTheOrganisationNameBlankAndConfirmTheLPAIsCorrect()
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa summary check
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'lpa' => $this->lpaData,
                            'expires' => (new DateTime('+30 days'))->format('c'),
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($params['name'], $this->lpaSurname);
                    assertEquals($params['code'], $this->lpaStoredCode);
                }
            );

        $this->ui->pressButton('View this LPA');
    }

    /**
     * @When /^I navigate to the viewer cookies page$/
     */
    public function iNavigateToTheViewerCookiesPage()
    {
        $this->ui->clickLink('cookie policy');
    }

    /**
     * @Given /^I navigate to the viewer privacy notice page$/
     */
    public function iNavigateToTheViewerPrivacyNoticePage()
    {
        $this->ui->clickLink('privacy notice');
        $this->ui->assertPageContainsText('View a lasting power of attorney');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    /**
     * @When /^I realise the LPA is incorrect$/
     */
    public function iRealiseTheLPAIsCorrect()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Try another access code');
    }

    /**
     * @When /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText('I want to check another LPA');
        $this->ui->clickLink('I want to check another LPA');
    }

    /**
     * @When /^I request to go back to the terms of use page$/
     */
    public function iRequestToGoBackToTheRequiredPage()
    {
        $this->ui->clickLink('Back');
    }

    /**
     * @When /^I request to see the viewer privacy notice$/
     */
    public function iRequestToSeeTheViewerPrivacyNotice()
    {
        $this->ui->clickLink('privacy notice');
    }

    /**
     * @When /^I request to see the viewer terms of use$/
     */
    public function iRequestToSeeTheViewerTermsOfUse()
    {
        $this->ui->clickLink('terms of use');
    }

    /**
     * @When /^I request to view an LPA with an invalid access code of "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWithAnInvalidAccessCodeOf($accessCode)
    {
        $this->ui->fillField('lpa_code', $accessCode);
        $this->ui->fillField('donor_surname', 'TestSurname');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to view an LPA with an invalid donor's surname of "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWithAnInvalidDonorSSurnameOf($surname)
    {
        $this->ui->fillField('lpa_code', 'T32TAC3SCOD3');
        $this->ui->fillField('donor_surname', $surname);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to view an LPA without entering a donor's surname$/
     */
    public function iRequestToViewAnLPAWithoutEnteringADonorSSurname()
    {
        $this->ui->fillField('lpa_code', 'ABCD1234EFGH');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I see a message that the share code has been cancelled$/
     */
    public function iSeeAMessageThatTheShareCodeHasBeenCancelled()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has been cancelled');
    }

    /**
     * @Then /^I see a message that the share code has expired$/
     */
    public function iSeeAMessageThatTheShareCodeHasBeenExpired()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has expired');
    }

    /**
     * @Given /^I view an LPA successfully$/
     */
    public function iViewAnLPASuccessfully()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAValidLPAShareCode();
        $this->iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect();
        $this->iAmViewingAValidLPA();
    }

    /**
     * @Given /^I waited too long to enter the share code$/
     */
    public function iWaitedTooLongToEnterTheShareCode()
    {
        $this->ui->getSession()->setCookie('session');
    }

    /**
     * @When /^I want to make an attempt to enter another share code$/
     */
    public function iWantToMakeAnAttemptToEnterAnotherShareCode()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Enter another code');
        $this->ui->clickLink('Enter another code');
    }

    /**
     * @Then /^I want to see an option to check another LPA$/
     */
    public function iWantToSeeAnOptionToCheckAnotherLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText('I want to check another LPA');
        $this->ui->clickLink('I want to check another LPA');
        $this->iGiveAValidLPAShareCode();
    }

    /**
     * @Then /^I want to see an option to re-enter code$/
     */
    public function iWantToSeeAnOptionToReEnterCode()
    {
        $this->ui->clickLink('Try another access code');
        $this->iGiveAValidLPAShareCode();
    }

    /**
     * @Then /^I want to see page to enter another share code$/
     */
    public function iWantToSeePageToEnterAnotherShareCode()
    {
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @When /^I click on the Read more link$/
     */
    public function iClickOnTheReadMoreLink()
    {
        $this->ui->clickLink('Read more');
    }

    /**
     * @Then /^I am taken to a page explaining why instructions and preferences are not available$/
     */
    public function iAmTakenToAPageExplainingWhyInstructionsAndPreferencesAreNotAvailable()
    {
        $this->ui->assertPageContainsText('Preferences and instructions cannot be shown for this LPA');
    }
}
