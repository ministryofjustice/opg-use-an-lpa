<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ContextUtilities;
use BehatTest\Context\ViewerContextTrait;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

class ViewerContext implements Context
{
    use BaseUiContextTrait;
    use ViewerContextTrait;

    private const LPA_SERVICE_GET_LPA_BY_CODE         = 'LpaService::getLpaByCode';
    private const SYSTEM_MESSAGE_SERVICE_GET_MESSAGES = 'SystemMessageService::getMessages';

    private $lpaSurname;
    private $lpaShareCode;
    private $lpaReferenceNumber;
    private $paperVerificationCode;
    private $lpaData;
    private $lpaStoredCode;
    private $lpaViewedBy;
    private $imageCollectionStatus;
    private $systemMessageData;

    #[Then('/^a PDF is downloaded$/')]
    public function aPDFIsDownloaded(): void
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

    #[Given('/^I access the viewer service$/')]
    #[Then('/^I am taken to viewer service home page$/')]
    public function iAccessTheViewerService(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home');
    }

    #[Given('/^A system message is set$/')]
    public function aSystemMessageIsSet(): void
    {
        $this->systemMessageData = [
            'view/en' => 'System Message View English',
            'view/cy' => 'System Message View Welsh',
        ];
    }

    #[Given('/^A system message is not set$/')]
    public function aSystemMessageIsNotSet(): void
    {
        $this->systemMessageData = [];
    }

    #[Given('/^I am on the enter code page$/')]
    #[Given('/^I am on the triage page$/')]
    #[Then('/^I am taken back to the enter code page$/')]
    public function iAmOnTheEnterCodePage(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home');
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Enter the LPA access code');
    }

    #[Given('/^I am on the stats page$/')]
    public function iAmOnTheStatsPage(): void
    {
        $this->ui->visit('/stats');
    }

    #[Given('/^I am on the viewer privacy notice page$/')]
    public function iAmOnTheViewerPrivacyNoticePage(): void
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageContainsText('View a lasting power of attorney');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    #[Given('/^I am on the viewer terms of use page$/')]
    public function iAmOnTheViewerTermsOfUsePage(): void
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    #[Given('/^I am shown the LPA summary found with valid credentials$/')]
    public function iAmShownTheLPASummaryFoundWithValidCredentials(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAValidLPAShareCode();
        $this->iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect();
        $this->iAmViewingAValidLPA();
    }

    #[Then('/^I am taken back to the terms of use page$/')]
    public function iAmTakenBackToTheTermsOfUsePage(): void
    {
        $this->iAmOnTheViewerTermsOfUsePage();
    }

    #[Then('/^I am taken to the session expired page$/')]
    public function iAmTakenToTheSessionExpiredPage(): void
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText("You'll have to start again");
    }

    #[Then('/^I am taken to the viewer cookies page$/')]
    public function iAmTakenToTheViewerCookiesPage(): void
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText('View a lasting power of attorney service');
    }

    #[Then('/^I am told that I must enter my organisation name$/')]
    public function iAmToldThatIMustEnterMyOrganisationName(): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Enter your organisation name');
    }

    #[Then('/^I am told that I must enter the donor\'s last name$/')]
    public function iAmToldThatIMustEnterTheDonorSLastName(): void
    {
        $this->ui->assertPageContainsText("Enter the donor's last name");
    }

    #[Then('/^I am told that my input is invalid because (.*)$/')]
    public function iAmToldThatMyInputIsInvalidBecause($reason): void
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText($reason);
    }

    #[Then('/^I am told that the share code is invalid because (.*)$/')]
    public function iAmToldThatTheShareCodeIsInvalidBecause($reason): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText($reason);
    }

    #[Given('/^I am told that we cannot currently get the instructions and preferences images$/')]
    public function iAmToldThatWeCannotCurrentlyGetTheInstructionsAndPreferencesImages(): void
    {
        $this->ui->assertElementNotOnPage('iap-preferences .iap-loader');
        $this->ui->assertElementNotContainsText('iap-preferences', 'A scanned image of the donor’s preferences will appear here soon');
        $this->ui->assertElementContainsText('iap-preferences', 'We cannot show the preferences for this');

        $this->ui->assertElementNotOnPage('iap-instructions .iap-loader');
        $this->ui->assertElementNotContainsText('iap-instructions', 'A scanned image of the donor’s instructions will appear here soon');
        $this->ui->assertElementContainsText('iap-instructions', 'We cannot show the instructions for this');
    }

    #[Given('/^I am told to wait for instructions and preferences images$/')]
    public function iAmToldToWaitForInstructionsAndPreferencesImages(): void
    {
        $this->ui->assertElementOnPage('iap-preferences .iap-loader');
        $this->ui->assertElementContainsText('iap-preferences', 'A scanned image of the donor’s preferences will appear here soon');
        $this->ui->assertElementNotContainsText('iap-preferences', 'We cannot show the preferences for this');

        $this->ui->assertElementOnPage('iap-instructions .iap-loader');
        $this->ui->assertElementContainsText('iap-instructions', 'A scanned image of the donor’s instructions will appear here soon');
        $this->ui->assertElementNotContainsText('iap-instructions', 'We cannot show the instructions for this');
    }

    #[Given('/^I am viewing a cancelled LPA$/')]
    #[Then('/^I can see the full details of the cancelled LPA$/')]
    public function iAmViewingACancelledLPA(): void
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

    #[Given('/^I am viewing a revoked LPA$/')]
    #[Then('/^I can see the full details of the revoked LPA$/')]
    public function iAmViewingARevokedLPA(): void
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

    #[Then('/^I can see the message (.*)$/')]
    public function iCanSeeTheMessage($message): void
    {
        $this->ui->assertPageContainsText($message);
    }

    #[Then('/^I cannot see the message (.*)$/')]
    public function iCanNotSeeTheMessage($message): void
    {
        $this->ui->assertPageNotContainsText($message);
    }

    #[Given('/^I am viewing a valid LPA$/')]
    #[Then('/^I can see the full details of the valid LPA$/')]
    public function iAmViewingAValidLPA(): void
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );
        $this->ui->assertPageContainsText('LPA is valid');
    }

    #[Given('/^I attempted an invalid share codes$/')]
    public function iAttemptedAnInvalidShareCodes(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAShareCodeThatHasExpired();
    }

    #[Then('/^I can see the accessibility statement for the View service$/')]
    public function iCanSeeTheAccessibilityStatementForTheViewService(): void
    {
        $this->ui->assertPageContainsText('Accessibility statement for View a lasting power of attorney');
    }

    #[Then('/^I can see the viewer privacy notice$/')]
    public function iCanSeeTheViewerPrivacyNotice(): void
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    #[Given('/^I can see the viewer\-specific text for the error message$/')]
    public function iCanSeeTheViewerSpecificTextForTheErrorMessage(): void
    {
        $this->ui->assertPageContainsText('you’ll need to ask the person who gave you the access code');
    }

    #[Then('/^I can see the viewer terms of use$/')]
    public function iCanSeeTheViewerTermsOfUse(): void
    {
        $this->ui->assertPageAddress('/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
    }

    #[Then('/^I can see user LPA codes table$/')]
    public function iCanSeeUserLPACodesTable(): void
    {
        $this->ui->assertPageAddress('/stats');
        $this->ui->assertPageContainsText('Number of LPA codes viewed');
    }

    #[When('/^I choose to download a document version of the LPA$/')]
    public function iChooseToDownloadADocumentVersionOfTheLPA(): void
    {
        $this->ui->assertPageAddress('/view-lpa');

        $data = [
            'lpa'     => $this->lpaData,
            'expires' => (new DateTime('+30 days'))->format('c'),
        ];

        $data['iap'] = [
            'uId'        => (int) $this->lpaData['uId'],
            'status'     => $this->imageCollectionStatus,
            'signedUrls' => [],
        ];

        // API call for lpa full fetch
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($data),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        // API to pdf service for pdf rendering
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, ''));

        $this->ui->pressButton('Download this LPA summary');

        //Full lpa fetch assertions
        $request = $this->base->mockClientHistoryContainer[3]['request'];
        $params  = json_decode((string) $request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($params['name'], $this->lpaSurname);
        Assert::assertEquals($params['code'], str_replace('-', '', $this->lpaShareCode));

        //PDF service assertions
        $request = $this->apiFixtures->getLastRequest();
        Assert::assertStringStartsWith('<!DOCTYPE html>', $request->getBody()->getContents());
    }

    #[When('/^I click the (.*) link on the page$/')]
    public function iClickTheBackLinkOnThePage($backLink): void
    {
        $this->ui->assertPageContainsText($backLink);
        $this->ui->clickLink($backLink);
    }

    #[When('/^I confirm the cancelled LPA is correct/')]
    public function iConfirmTheCancelledLPAIsCorrect(): void
    {
        $this->lpaData['status']           = 'Cancelled';
        $this->lpaData['cancellationDate'] = (new DateTime('-1 day'))->format('Y-m-d');

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'       => $this->lpaData,
                        'expires'   => (new DateTime('+30 days'))->format('c'),
                        'cancelled' => (new DateTime('-1 day'))->format('c'),
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($params['name'], $this->lpaSurname);
        Assert::assertEquals($params['code'], $this->lpaStoredCode);
        Assert::assertEquals($params['organisation'], $this->lpaViewedBy);
    }

    #[When('/^I confirm the revoked LPA is correct/')]
    public function iConfirmTheRevokedLPAIsCorrect(): void
    {
        $this->lpaData['status'] = 'Revoked';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'       => $this->lpaData,
                        'expires'   => (new DateTime('+30 days'))->format('c'),
                        'cancelled' => (new DateTime('-1 day'))->format('c'),
                    ]
                )
            )
        )
            ->inspectRequest(
                function (RequestInterface $request): void {
                    $params = json_decode($request->getBody()->getContents(), true);

                    Assert::assertIsArray($params);
                    Assert::assertEquals($params['name'], $this->lpaSurname);
                    Assert::assertEquals($params['code'], $this->lpaStoredCode);
                    Assert::assertEquals($params['organisation'], $this->lpaViewedBy);
                }
            );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');
    }

    #[When('/^I enter an organisation name$/')]
    public function iEnterAnOrganisationName(): void
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Ask the donor or attorney for a new access code if your organisation:');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa full fetch
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'     => $this->lpaData,
                        'expires' => (new DateTime('+30 days'))->format('c'),
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');
    }

    #[When('/^I enter an organisation name and confirm the LPA is correct$/')]
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Ask the donor or attorney for a new access code if your organisation:');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        $data = [
            'lpa'     => $this->lpaData,
            'expires' => (new DateTime('+30 days'))->format('c'),
        ];

        if (
            ($data['lpa']['applicationHasGuidance'] ?? false) || ($data['lpa']['applicationHasRestrictions'] ?? false)
        ) {
            $data['iap'] = [
                'uId'        => (int) $this->lpaData['uId'],
                'status'     => $this->imageCollectionStatus,
                'signedUrls' => [],
            ];

            if ($data['lpa']['applicationHasGuidance'] ?? false) {
                $data['iap']['signedUrls']['iap-' . $this->lpaData['uId'] . '-preferences']
                    = 'https://images/image.jpg';
            }

            if ($data['lpa']['applicationHasRestrictions'] ?? false) {
                $data['iap']['signedUrls']['iap-' . $this->lpaData['uId'] . '-instructions']
                    = 'https://images/image.jpg';
            }
        }

        // API call for lpa full fetch
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($data),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('organisation', $this->lpaViewedBy);
        $this->ui->pressButton('View this LPA');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($params['name'], $this->lpaSurname);
        Assert::assertEquals($params['code'], $this->lpaStoredCode);
        Assert::assertEquals($params['organisation'], $this->lpaViewedBy);
    }

    #[When('/^I give a share code that has expired$/')]
    public function iGiveAShareCodeThatHasExpired(): void
    {
        $this->lpaData['status'] = 'Expired';
        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                json_encode(
                    [
                        'title'   => 'Gone',
                        'details' => 'Share code expired',
                        'data'    => [],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I give a share code that\'s been cancelled$/')]
    public function iGiveAShareCodeThatsBeenCancelled(): void
    {
        $this->lpaData['status'] = 'Cancelled';
        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                json_encode(
                    [
                        'title'   => 'Gone',
                        'details' => 'Share code cancelled',
                        'data'    => [],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I give a valid LPA share code on a cancelled LPA$/')]
    public function iGiveAValidCancelledLPAShareCode(): void
    {
        $this->lpaData['status'] = 'Cancelled';

        $this->giveAValidLpaShareCode();
    }

    #[Given('/^The LPA has instructions and preferences$/')]
    public function theLPAHasInstructionsAndPreferences(): void
    {
        $this->lpaData['lpaDonorSignatureDate']      = '2016-01-01';
        $this->lpaData['applicationHasGuidance']     = true;
        $this->lpaData['applicationHasRestrictions'] = true;

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';

        $this->giveAValidLpaShareCode();
    }

    #[Given('/^The LPA has instructions$/')]
    public function theLPAHasInstructions(): void
    {
        $this->lpaData['lpaDonorSignatureDate']      = '2016-01-01';
        $this->lpaData['applicationHasGuidance']     = false;
        $this->lpaData['applicationHasRestrictions'] = true;

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';

        $this->giveAValidLpaShareCode();
    }

    #[Given('/^The LPA has instructions and preferences for which image collection is not yet started$/')]
    public function theLPAHasInstructionsAndPreferencesForWhichImageCollectionIsNotYetStarted(): void
    {
        $this->theLPAHasInstructionsAndPreferences();

        $this->imageCollectionStatus = 'COLLECTION_NOT_STARTED';
    }

    #[Given('/^The LPA has instructions and preferences for which images aren\'t yet ready$/')]
    public function theLPAHasInstructionsAndPreferencesForWhichImagesArenTYetReady(): void
    {
        $this->theLPAHasInstructionsAndPreferences();

        $this->imageCollectionStatus = 'COLLECTION_IN_PROGRESS';
    }

    #[Given('/^The LPA has instructions and preferences for which images will fail to load$/')]
    public function theLPAHasInstructionsAndPreferencesForWhichImagesWillFailToLoad(): void
    {
        $this->theLPAHasInstructionsAndPreferences();

        $this->imageCollectionStatus = 'COLLECTION_ERROR';
    }

    #[Given('/^The LPA has preferences$/')]
    public function theLPAHasPreferences(): void
    {
        $this->lpaData['lpaDonorSignatureDate']      = '2016-01-01';
        $this->lpaData['applicationHasGuidance']     = true;
        $this->lpaData['applicationHasRestrictions'] = false;

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';

        $this->giveAValidLpaShareCode();
    }

    #[Given('/^The LPA has no instructions or preferences and is signed before 2016$/')]
    public function theLPAHasNoInstructionsOrPreferences(): void
    {
        $this->lpaData['lpaDonorSignatureDate']      = '2014-01-01';
        $this->lpaData['applicationHasGuidance']     = false;
        $this->lpaData['applicationHasRestrictions'] = false;

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';

        $this->giveAValidLpaShareCode();
    }

    #[Given('/^The LPA has instructions and preferences and is signed before 2016$/')]
    public function theLPAHasInstructionsAndPreferencesAndIsSignedBefore2016(): void
    {
        $this->lpaData['applicationHasGuidance']     = true;
        $this->lpaData['applicationHasRestrictions'] = true;

        $this->lpaData['lpaDonorSignatureDate'] = '2015-01-01';

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';

        $this->giveAValidLpaShareCode();
    }

    #[When('/^I give a valid LPA share code$/')]
    public function iGiveAValidLPAShareCode(): void
    {
        $this->lpaData['status'] = 'Registered';

        $this->giveAValidLpaShareCode();
    }

    #[When('/^I give a valid LPA share code when my session is timed out$/')]
    public function iGiveAValidLPAShareCodeWhenMySessionIsTimedOut(): void
    {
        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I give a valid LPA share code of (.*) which matches (.*)$/')]
    public function iGiveAValidLPAShareCodeOf(string $code, string $storedCode): void
    {
        $this->lpaShareCode  = $code;
        $this->lpaStoredCode = $storedCode;
        $this->iGiveAValidLPAShareCode();
    }

    #[Then('/^I can clearly see the lpa has instructions and preferences$/')]
    public function iCanClearlySeeTheLPAHasInstructionsAndPreferences(): void
    {
        $this->ui->assertElementContainsText('div.govuk-panel', 'This LPA has preferences and instructions');
        $this->ui->assertElementOnPage('iap-instructions img.opg-ip__image');
        $this->ui->assertElementOnPage('iap-preferences img.opg-ip__image');
    }

    #[Then('/^I can clearly see the lpa has preferences$/')]
    public function iCanClearlySeeTheLPAHasPreferences(): void
    {
        $this->ui->assertElementContainsText('div.govuk-panel', 'This LPA has preferences');
        $this->ui->assertElementNotOnPage('iap-instructions img.opg-ip__image');
        $this->ui->assertElementOnPage('iap-preferences img.opg-ip__image');
    }

    #[Then('/^I can clearly see the lpa has instructions$/')]
    public function iCanClearlySeeTheLPAHasInstructions(): void
    {
        $this->ui->assertElementContainsText('div.govuk-panel', 'This LPA has instructions');
        $this->ui->assertElementOnPage('iap-instructions img.opg-ip__image');
        $this->ui->assertElementNotOnPage('iap-preferences img.opg-ip__image');
    }

    #[When('/^I give an invalid (.*) and (.*)$/')]
    public function iGiveAnInvalidShareCodeAndSurname($shareCode, $surname): void
    {
        $this->ui->assertPageAddress('/home');

        // API call for lpa summary check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                '',
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('donor_surname', $surname);
        $this->ui->fillField('lpa_code', $shareCode);
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I have an error message informing me to try again\.$/')]
    public function iHaveAnErrorMessageInformingMeToTryAgain(): void
    {
        $this->ui->assertPageContainsText(
            "As you have not used this service for over 20 minutes, the page has timed out. We've now " .
                'refreshed the page - please try to sign in again'
        );
    }

    #[Given('/^I have been given access to a cancelled LPA via share code$/')]
    public function iHaveBeenGivenAccessToACancelledLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Cancelled';
    }

    #[Given('/^I have been given access to an expired LPA via share code$/')]
    public function iHaveBeenGivenAccessToAnExpiredLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Expired';
    }

    #[Given('/^I have been given access to an LPA via share code$/')]
    public function iHaveBeenGivenAccessToAnLPAViaShareCode(): void
    {
        $this->lpaSurname    = 'Testerson';
        $this->lpaShareCode  = '1111-1111-1111';
        $this->lpaStoredCode = '111111111111';
        $this->lpaViewedBy   = 'Santander';
        $this->lpaData       = [
            'id'                    => 1,
            'uId'                   => '700000000000',
            'receiptDate'           => '2014-09-26',
            'registrationDate'      => '2014-10-26',
            'lpaDonorSignatureDate' => '2015-06-30',
            'donor'                 => [
                'id'          => 1,
                'uId'         => '700000000288',
                'dob'         => '1948-11-01',
                'salutation'  => 'Mr',
                'firstname'   => 'Test',
                'middlenames' => 'Testable',
                'surname'     => 'Testerson',
                'addresses'   => [
                    0 => [
                        'id'           => 1,
                        'town'         => 'Test',
                        'county'       => 'Testershire',
                        'postcode'     => 'TE57 7ES',
                        'country'      => '',
                        'type'         => 'Primary',
                        'addressLine1' => 'Test House',
                        'addressLine2' => 'Test Road',
                        'addressLine3' => '',
                    ],
                ],
            ],
            'status'                => 'Registered',
            'caseSubtype'           => 'hw',
        ];

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';
    }

    #[When('/^I leave the organisation name blank and confirm the LPA is correct$/')]
    public function iLeaveTheOrganisationNameBlankAndConfirmTheLPAIsCorrect(): void
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText(
            $this->lpaData['donor']['firstname'] . ' ' . $this->lpaData['donor']['surname']
        );

        // API call for lpa summary check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'     => $this->lpaData,
                        'expires' => (new DateTime('+30 days'))->format('c'),
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->pressButton('View this LPA');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($params['name'], $this->lpaSurname);
        Assert::assertEquals($params['code'], $this->lpaStoredCode);
    }

    #[When('/^I navigate to the viewer cookies page$/')]
    public function iNavigateToTheViewerCookiesPage(): void
    {
        $this->ui->clickLink('cookie policy');
    }

    #[Given('/^I navigate to the viewer privacy notice page$/')]
    public function iNavigateToTheViewerPrivacyNoticePage(): void
    {
        $this->ui->clickLink('privacy notice');
        $this->ui->assertPageContainsText('View a lasting power of attorney');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    #[When('/^I realise the LPA is incorrect$/')]
    public function iRealiseTheLPAIsCorrect(): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Try another access code');
    }

    #[When('/^I request to go back and try again$/')]
    public function iRequestToGoBackAndTryAgain(): void
    {
        $this->ui->assertPageAddress('/view-lpa');
        $this->ui->assertPageContainsText('I want to check another LPA');
        $this->ui->clickLink('I want to check another LPA');
    }

    #[When('/^I request to go back to the terms of use page$/')]
    public function iRequestToGoBackToTheRequiredPage(): void
    {
        $this->ui->clickLink('Back');
    }

    #[When('/^I request to see the viewer privacy notice$/')]
    public function iRequestToSeeTheViewerPrivacyNotice(): void
    {
        $this->ui->clickLink('privacy notice');
    }

    #[When('/^I request to see the viewer terms of use$/')]
    public function iRequestToSeeTheViewerTermsOfUse(): void
    {
        $this->ui->clickLink('terms of use');
    }

    #[When('/^I request to view an LPA with an invalid access code of "([^"]*)"$/')]
    public function iRequestToViewAnLPAWithAnInvalidAccessCodeOf($accessCode): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->fillField('lpa_code', $accessCode);
        $this->ui->fillField('donor_surname', 'TestSurname');
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to view an LPA with an invalid donor\'s surname of "([^"]*)"$/')]
    public function iRequestToViewAnLPAWithAnInvalidDonorSSurnameOf($surname): void
    {
        $this->ui->fillField('lpa_code', 'T32TAC3SCOD3');
        $this->ui->fillField('donor_surname', $surname);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I request to view an LPA without entering a donor\'s surname$/')]
    public function iRequestToViewAnLPAWithoutEnteringADonorSSurname(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->fillField('lpa_code', 'ABCD1234EFGH');
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I see a message that the share code has been cancelled$/')]
    public function iSeeAMessageThatTheShareCodeHasBeenCancelled(): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has been cancelled');
    }

    #[Then('/^I see a message that the share code has expired$/')]
    public function iSeeAMessageThatTheShareCodeHasBeenExpired(): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('The access code you entered has expired');
    }

    #[Given('/^I view an LPA successfully$/')]
    public function iViewAnLPASuccessfully(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();
        $this->iAccessTheViewerService();
        $this->iGiveAValidLPAShareCode();
        $this->iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect();
        $this->iAmViewingAValidLPA();
    }

    #[Given('/^I waited too long to enter the share code$/')]
    public function iWaitedTooLongToEnterTheShareCode(): void
    {
        $this->ui->getSession()->setCookie('__Host-session');
    }

    #[When('/^I want to make an attempt to enter another share code$/')]
    public function iWantToMakeAnAttemptToEnterAnotherShareCode(): void
    {
        $this->ui->assertPageAddress('/check-code');
        $this->ui->assertPageContainsText('Enter another code');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->clickLink('Enter another code');
    }

    #[Then('/^I want to see an option to check another LPA$/')]
    public function iWantToSeeAnOptionToCheckAnotherLPA(): void
    {
        $this->ui->assertPageContainsText('I want to check another LPA');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->clickLink('I want to check another LPA');
        $this->iGiveAValidLPAShareCode();
    }

    #[Then('/^I want to see an option to re-enter code$/')]
    public function iWantToSeeAnOptionToReEnterCode(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->clickLink('Try another access code');
        $this->iGiveAValidLPAShareCode();
    }

    #[Then('/^I want to see page to enter another share code$/')]
    public function iWantToSeePageToEnterAnotherShareCode(): void
    {
        $this->ui->assertPageAddress('/home');
    }

    #[When('/^I click on the Read more link$/')]
    public function iClickOnTheReadMoreLink(): void
    {
        $this->ui->clickLink('Read more');
    }

    #[Then('/^I am taken to a page explaining why instructions and preferences are not available$/')]
    public function iAmTakenToAPageExplainingWhyInstructionsAndPreferencesAreNotAvailable(): void
    {
        $this->ui->assertPageContainsText('Preferences and instructions cannot be shown for this LPA');
    }

    private function giveAValidLpaShareCode(): void
    {
        $this->ui->assertPageAddress('/home');

        // API call for lpa summary check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'     => $this->lpaData,
                        'expires' => (new DateTime('+30 days'))->format('c'),
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');

        $request = $this->apiFixtures->getLastRequest();

        $params = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($params['name'], $this->lpaSurname);
        Assert::assertEquals($params['code'], $this->lpaStoredCode);
    }

    #[Given('/^I have been given access to an LPA via Paper Verification Code$/')]
    public function iHaveBeenGivenAccessToAnLPAViaPaperVerificationCOde(): void
    {
        $this->lpaSurname    = 'Testerson';
        $this->lpaReferenceNumber    = 'M-1234-1234-1234';
        $this->paperVerificationCode  = 'P-AB12-CD34-EF56-G7';
        $this->lpaStoredCode = '111111111111';
        $this->lpaViewedBy   = 'Santander';
        $this->lpaData       = [
            'id'                    => 1,
            'uId'                   => '700000000000',
            'receiptDate'           => '2014-09-26',
            'registrationDate'      => '2014-10-26',
            'lpaDonorSignatureDate' => '2015-06-30',
            'donor'                 => [
                'id'          => 1,
                'uId'         => '700000000288',
                'dob'         => '1948-11-01',
                'salutation'  => 'Mr',
                'firstname'   => 'Test',
                'middlenames' => 'Testable',
                'surname'     => 'Testerson',
                'addresses'   => [
                    0 => [
                        'id'           => 1,
                        'town'         => 'Test',
                        'county'       => 'Testershire',
                        'postcode'     => 'TE57 7ES',
                        'country'      => '',
                        'type'         => 'Primary',
                        'addressLine1' => 'Test House',
                        'addressLine2' => 'Test Road',
                        'addressLine3' => '',
                    ],
                ],
            ],
            'status'                => 'Registered',
            'caseSubtype'           => 'hw',
        ];

        $this->imageCollectionStatus = 'COLLECTION_COMPLETE';
    }

    public function appendSystemMessageFixture(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessageData ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );
    }

    #[When('/^I give a valid LPA Paper Verification Code$/')]
    public function iGiveAValidLPAPaperVerificationCode(): void
    {
        $this->lpaData['status'] = 'Registered';

        $this->ui->assertPageAddress('/home');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'lpa'     => $this->lpaData,
                        'expires' => (new DateTime('+30 days'))->format('c'),
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->paperVerificationCode);
        $this->appendSystemMessageFixture();
        $this->ui->pressButton('Continue');
    }

    #[Then('/^I will be asked to enter more information$/')]
    public function iWillBeAskedToEnterMoreInformation(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-code');
        $this->ui->assertPageContainsText('We need some more details');
    }

    #[Given('/^I type in a valid LPA reference number$/')]
    public function iTypeAValidLpaReferenceNumber(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-code');
        $this->ui->assertPageContainsText('We need some more details');

        $this->ui->fillField('lpa_reference', 'M-1234-1234-1234');
    }

    #[When('/^I select continue$/')]
    public function iSelectContinue(): void
    {
        $this->appendSystemMessageFixture();
        $this->ui->pressButton('Continue');
        $this->appendSystemMessageFixture();
    }

    #[Then('/^I will be asked who the paper verification code was sent to$/')]
    public function iWillBeAskedWhoThePaperVerificationCodeWasSentTo(): void
    {
        $this->ui->assertPageAddress('/paper-verification/verification-code-sent-to');
    }

    #[Given('/^(.*) was chosen as the person who the paper verification code was sent to$/')]
    public function attorneyWasChosenAsThePersonWhoThePaperVerificationCodeWasSentTo($codeSentTo): void
    {
        $this->ui->assertPageAddress('/paper-verification/verification-code-sent-to');

        $this->ui->fillField('verification_code_receiver', $codeSentTo);

        if ($codeSentTo === 'Attorney') {
            $this->ui->fillField('attorney_name', 'Barabara');
        }
    }

    #[Then('/^they will see a page asking for attorney dob$/')]
    public function theyWillSeeAPageAskingForAttorneyDob(): void
    {
        $this->ui->assertPageAddress('/paper-verification/attorney-dob');
    }

    #[Then('/^they will see a page asking for donor dob$/')]
    public function theyWillSeeAPageAskingForDonorDob(): void
    {
        $this->ui->assertPageAddress('/paper-verification/donor-dob');
    }

    #[Given('/^paper verification code is for the attorney$/')]
    public function paperVerificationCodeIsForTheAttorney(): void
    {

    }

    #[Given('/^paper verification code is for the donor/')]
    public function paperVerificationCodeIsForTheDonor(): void
    {

    }

    #[When('/^they have entered date of birth for (.*)$/')]
    public function theyHaveEnteredDateOfBirth($codeSentTo): void
    {
        $this->ui->assertPageAddress('/paper-verification/' . $codeSentTo . '-dob');

        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');

        $this->appendSystemMessageFixture();
        $this->ui->pressButton('Continue');
    }

    #[When('/^they have entered number of attorneys$/')]
    public function theyHaveEnteredNumberOfAttorneys(): void
    {
        $this->appendSystemMessageFixture();
        $this->ui->assertPageAddress('/paper-verification/number-of-attorneys');

        $this->ui->fillField('no_of_attorneys', '2');
        $this->appendSystemMessageFixture();
        $this->ui->pressButton('Continue');
    }

    #[When('/^they have entered attorney details$/')]
    public function theyHaveEnteredAttorneyDetails(): void
    {
        $this->appendSystemMessageFixture();
        $this->ui->assertPageAddress('/paper-verification/provide-attorney-details');

        $this->ui->fillField('no_of_attorneys', '2');
        $this->ui->fillField('attorneys_name', 'Barabara');

        $this->ui->pressButton('Continue');
    }

    #[Then('/^they check their answers$/')]
    public function theyCheckTheirAnswers(): void
    {
        $this->appendSystemMessageFixture();
        $this->ui->assertPageAddress('/paper-verification/check-answers');
    }

    #[Given('/^they change LPA Reference on check answers page$/')]
    public function theyChangeLpaReferenceOnCheckAnswersPage(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-answers');

        $link = $this->ui->getSession()->getPage()->find('xpath', '//a[contains(@href,"/paper-verification/check-code")]');
        if ($link === null) {
            throw new Exception('Change link not found');
        }
        $this->appendSystemMessageFixture();
        $link->click();
        $this->ui->assertPageAddress('/paper-verification/check-code');
     }

    #[When('/^they click continue they return to check answers page$/')]
    public function theyClickContinueTheyReturnToCheckAnswersPage(): void
    {
        $this->appendSystemMessageFixture();
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('/paper-verification/check-answers');
    }

    #[Given('/^they change who code sent to on check answers page$/')]
    public function theyChangeWhoCodeSentToOnCheckAnswersPage(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-answers');

        $link = $this->ui->getSession()->getPage()->find('xpath', '//a[contains(@href,"/paper-verification/verification-code-sent-to")]');
        if ($link === null) {
            throw new Exception('Change link not found');
        }
        $this->appendSystemMessageFixture();
        $link->click();
        $this->ui->assertPageAddress('/paper-verification/verification-code-sent-to');
    }

    #[When('/^they click back they return to check answers page$/')]
    public function theyClickBackTheyReturnToCheckAnswersPage(): void
    {
        $this->ui->clickLink('Back');
        $this->ui->assertPageAddress('/paper-verification/check-answers');
    }

    #[Given('/^they change attorney dob on check answers page$/')]
    public function theyChangeAttorneyDobOnCheckAnswersPage(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-answers');

        $link = $this->ui->getSession()->getPage()->find('xpath', '//a[contains(@href,"/paper-verification/attorney-dob")]');
        if ($link === null) {
            throw new Exception('Change link not found');
        }
        $this->appendSystemMessageFixture();
        $link->click();
        $this->ui->assertPageAddress('/paper-verification/attorney-dob');
    }

    #[Given('/^they change number of attorney on check answers page$/')]
    public function theyChangeNumberOfAttorneyOnCheckAnswersPage(): void
    {
        $this->ui->assertPageAddress('/paper-verification/check-answers');

        $link = $this->ui->getSession()->getPage()->find('xpath', '//a[contains(@href,"/paper-verification/number-of-attorneys")]');
        if ($link === null) {
            throw new Exception('Change link not found');
        }
        $this->appendSystemMessageFixture();
        $link->click();
        $this->ui->assertPageAddress('/paper-verification/number-of-attorneys');
    }
}
