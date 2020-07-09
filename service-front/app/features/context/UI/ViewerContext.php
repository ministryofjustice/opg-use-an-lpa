<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ViewerContextTrait;
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
 */
class ViewerContext implements Context
{
    use ViewerContextTrait;
    use BaseUiContextTrait;

    /**
     * @Given /^I have been given access to an LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnLPAViaShareCode()
    {
        $this->lpaSurname = 'Testerson';
        $this->lpaShareCode = '1111-1111-1111';
        $this->lpaStoredCode = '111111111111';
        $this->lpaData = [
            'id' => 1,
            'uId' => '7000-0000-0000',
            'receiptDate' => '2014-09-26',
            'registrationDate' => '2014-10-26',
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
                        'addressLine3' => ''
                    ]
                ]
            ]
        ];
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
     * @Given /^I access the viewer service$/
     */
    public function iAccessTheViewerService()
    {
        $this->ui->iAmOnHomepage();
        $this->ui->assertElementContainsText('a[name=viewer-start]', 'Start');
        $this->ui->clickLink('Start');
    }

    /**
     * @When /^I give a valid LPA share code of (.*) which matches (.*)$/
     */
    public function iGiveAValidLPAShareCodeOf(string $code, string $storedCode)
    {
        $this->lpaShareCode =  $code;
        $this->lpaStoredCode = $storedCode;
        $this->iGiveAValidLPAShareCode();
    }
    /**
     * @When /^I give a valid LPA share code$/
     */
    public function iGiveAValidLPAShareCode()
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/enter-code');

        // API call for lpa summary check
        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'lpa'     => $this->lpaData,
                'expires' => (new \DateTime('+30 days'))->format('c')
            ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($params['name'], $this->lpaSurname);
                assertEquals($params['code'], $this->lpaStoredCode);
            });

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I confirm the LPA is correct$/
     */
    public function iConfirmTheLPAIsCorrect()
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'lpa'     => $this->lpaData,
                'expires' => (new \DateTime('+30 days'))->format('c')
            ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($params['name'], $this->lpaSurname);
                assertEquals($params['code'], $this->lpaStoredCode);
            });

        $this->ui->clickLink('Continue');
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
     * @Then /^I see a message that LPA has been cancelled$/
     */
    public function iSeeAMessageThatLPAHasBeenCancelled()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has expired');
    }

    /**
     * @Then /^I see a message that LPA has been expired$/
     */
    public function iSeeAMessageThatLPAHasBeenExpired()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has expired');
    }

    /**
     * @When /^I choose to download a document version of the LPA$/
     */
    public function iChooseToDownloadADocumentVersionOfTheLPA()
    {
        $this->ui->assertPageAddress('/view-lpa');

        // API call for lpa full fetch
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'lpa'     => $this->lpaData,
                'expires' => (new \DateTime('+30 days'))->format('c')
            ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($params['name'], $this->lpaSurname);
                assertEquals($params['code'], str_replace('-', '', $this->lpaShareCode));
            });

        // API to pdf service for pdf rendering
        $this->apiFixtures->post('/generate-pdf')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], ''))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                assertStringStartsWith('<!DOCTYPE html>', $request->getBody()->getContents());
            });

        $this->ui->clickLink('Download this LPA summary');
    }

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
     * @Given /^I am on the enter code page$/
     */
    public function iAmOnTheEnterCodePage()
    {
        $this->ui->visit('/enter-code');
        $this->ui->assertPageAddress('/enter-code');
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
     * @Then /^I am told that my input is invalid because (.*)$/
     */
    public function iAmToldThatMyInputIsInvalidBecause($reason)
    {
        $this->ui->assertPageAddress('/enter-code');
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I give a share code that has got expired$/
     */
    public function iGiveAShareCodeThatHasGotExpired()
    {
        $this->lpaData['status'] = 'Expired';
        $this->ui->assertPageAddress('/enter-code');

        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE, [], json_encode([
                'title' => 'Gone',
                'details' => 'Share code expired',
                'data' => [],
            ])));

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
        $this->ui->assertPageAddress('/enter-code');

        $this->apiFixtures->post('/v1/viewer-codes/summary')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE, [], json_encode([
                'title' => 'Gone',
                'details' => 'Share code cancelled',
                'data' => [],
            ])));

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I give an invalid (.*) and (.*)$/
     */
    public function iGiveAnInvalidShareCodeAndSurname($shareCode, $surname)
    {
        $this->ui->assertPageAddress('/enter-code');

        // API call for lpa summary check
        $data = $this->apiFixtures->post('/v1/viewer-codes/summary')
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
     * @Then /^I am told that the share code is invalid because (.*)$/
     */
    public function iAmToldThatTheShareCodeIsInvalidBecause($reason)
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @Given /^I attempted an invalid share codes$/
     */
    public function iAttemptedAnInvalidShareCodes()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAShareCodeThatHasGotExpired();
    }

    /**
     * @When /^I want to make an attempt to enter another share code$/
     */
    public function iWantToMakeAnAttemptToEnterAnotherShareCode()
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText("Enter another code");
        $this->ui->clickLink('Enter another code');
    }

    /**
     * @Then /^I want to see page to enter another share code$/
     */
    public function iWantToSeePageToEnterAnotherShareCode()
    {
        $this->ui->assertPageAddress('/enter-code');
    }

    /**
     * @Given /^I am shown the LPA summary found with valid credentials$/
     */
    public function iAmShownTheLPASummaryFoundWithValidCredentials()
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAValidLPAShareCode();
        $this->iConfirmTheLPAIsCorrect();
        $this->iAmViewingAValidLPA();
    }

    /**
     * @When /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText("I want to check another LPA");
        $this->ui->clickLink("I want to check another LPA");
    }

    /**
     * @When /^I request to see the viewer terms of use$/
     */
    public function iRequestToSeeTheViewerTermsOfUse()
    {
        $this->ui->clickLink("terms of use");
    }

    /**
     * @When /^I request to see the viewer privacy notice$/
     */
    public function iRequestToSeeTheViewerPrivacyNotice()
    {
        $this->ui->clickLink("privacy notice");
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
     * @Then /^I can see the viewer privacy notice$/
     */
    public function iCanSeeTheViewerPrivacyNotice()
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    /**
     * @Given /^I am on the terms of use page$/
     */
    public function iAmOnTheTermsOfUsePage()
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Given /^I am on the privacy notice page$/
     */
    public function iAmOnThePrivacyNoticePage()
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    /**
     * @When /^I request to go back to the enter code page$/
     * @When /^I request to go back to the terms of use page$/
     */
    public function iRequestToGoBackToTheRequiredPage()
    {
        $this->ui->clickLink('Back');
    }

    /**
     * @Then /^I am taken back to the enter code page$/
     */
    public function iAmTakenBackToTheEnterCodePage()
    {
        $this->ui->assertPageAddress('/enter-code');
        $this->ui->assertPageContainsText('Enter the LPA access code');
    }

    /**
     * @Then /^I am taken back to the terms of use page$/
     */
    public function iAmTakenBackToTheTermsOfUsePage()
    {
        $this->iAmOnTheTermsOfUsePage();
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
     * @Then /^I want to see an option to re-enter code$/
     */
    public function iWantToSeeAnOptionToReEnterCode()
    {
        $this->ui->clickLink('Try another access code');
        $this->iGiveAValidLPAShareCode();
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
     * @Given /^I waited too long to enter the share code$/
     */
    public function iWaitedTooLongToEnterTheShareCode()
    {
        $this->ui->getSession()->setCookie('session', null);
    }

    /**
     * @Then /^I have an error message informing me to try again\.$/
     */
    public function iHaveAnErrorMessageInformingMeToTryAgain()
    {
        $this->iAmTakenBackToTheEnterCodePage();
        $this->ui->assertPageContainsText("Do you want to continue?" .
        " You have not used this service for 30 minutes." .
        " Click continue to use any details you entered");
    }

    /**
     * @Given /^I am on the stats page$/
     */
    public function iAmOnTheStatsPage()
    {
        $this->ui->visit('/stats');
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
     * @Then /^I am taken to the session expired page$/
     */
    public function iAmTakenToTheSessionExpiredPage()
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText('You\'ll have to start again');
    }
}
