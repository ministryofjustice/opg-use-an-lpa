<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use BehatTest\Context\ContextUtilities;
use BehatTest\Context\ViewerContextTrait;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\Assert;

/**
 * Class ViewerContext
 *
 * @property $lpaSurname
 * @property $lpaShareCode
 * @property $lpaData
 * @property $viewedLpa
 * @property $lpaViewedBy
 */
class ViewerContext extends BaseIntegrationContext
{
    use ViewerContextTrait;


    private const LPA_SERVICE_GET_LPA_BY_CODE = 'LpaService::getLpaByCode';

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
    }

    /**
     * @Given /^I have been given access to an LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnLPAViaShareCode(): void
    {
        $this->lpaShareCode = '1111-1111-1111';
        $this->lpaSurname = 'Testerson';
        $this->lpaViewedBy = 'Santander';
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
    public function iHaveBeenGivenAccessToACancelledLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Cancelled';
    }


    /**
     * @Given /^I have been given access to a revoked LPA via share code$/
     */
    public function iHaveBeenGivenAccessToARevokedLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToAnLPAViaShareCode();

        $this->lpaData['status'] = 'Revoked';
    }

    /**
     * @Given /^I have been given access to an expired LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnExpiredLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToAnExpiredLPAViaShareCode();

        $this->lpaData['status'] = 'Expired';
    }

    /**
     * @Given /^I access the viewer service$/
     */
    public function iAccessTheViewerService(): void
    {
        // not used in this context
    }

    /**
     * @When /^I give a valid LPA share code$/
     */
    public function iGiveAValidLPAShareCode(): void
    {
        // not used in this context
    }

    /**
    * @When /^I give a valid LPA share code on a cancelled LPA$/
    */
    public function iGiveAValidLPAShareCodeOnACancelledLPA(): void
    {
        // not used in this context
    }

    /**
     * @When /^I enter an organisation name and confirm the LPA is correct$/
     */
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        // not used in this context
    }

    /**
     * @When /^I confirm the cancelled LPA is correct$/
     */
    public function iConfirmTheCancelledLPAIsCorrect(): void
    {
        // not used in this context
    }


    /**
     * @When /^I give a share code that's been cancelled$/
     */
    public function iGiveAShareCodeThatsBeenCancelled(): void
    {
        // not used in this context
    }

    /**
    * @When /^I give a share code that's been revoked$/
    */
    public function iGiveAShareCodeThatsBeenRevoked(): void
    {
        // not used in this context
    }

    /**
     * @Given /^I am viewing a valid LPA$/
     * @Then /^I can see the full details of the valid LPA$/
     * @Then /^I see a message that LPA has been cancelled$/
     */
    public function iAmViewingAValidLPA(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([
                                'lpa' => $this->lpaData
                            ]),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $lpaService = $this->container->get(LpaService::class);

        $this->viewedLpa = ($lpaService->getLpaByCode(
            $this->lpaShareCode,
            $this->lpaSurname,
            $this->lpaViewedBy
        ))['lpa'];

        $request = $this->apiFixtures->getLastRequest();
        $body = json_decode($request->getBody()->getContents());

        Assert::assertEquals('111111111111', $body->code); // code gets hyphens removed
        Assert::assertEquals($this->lpaSurname, $body->name);
        Assert::assertEquals($this->lpaViewedBy, $body->organisation);
    }

    /**
     * @Then /^I can see the full details of the cancelled LPA$/
     */
    public function iAmViewingACancelledLPA(): void
    {
        $this->lpaData['status'] = 'Cancelled';
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([
                                'lpa' => $this->lpaData
                            ]),
                self::LPA_SERVICE_GET_LPA_BY_CODE
            )
        );

        $lpaService = $this->container->get(LpaService::class);

        $this->viewedLpa = ($lpaService->getLpaByCode(
            $this->lpaShareCode,
            $this->lpaSurname,
            $this->lpaViewedBy
        ))['lpa'];

        $request = $this->apiFixtures->getLastRequest();
        $body = json_decode($request->getBody()->getContents());

        Assert::assertEquals('111111111111', $body->code); // code gets hyphens removed
        Assert::assertEquals($this->lpaSurname, $body->name);
        Assert::assertEquals($this->lpaViewedBy, $body->organisation);
    }
    /**
     * @When /^I choose to download a document version of the LPA$/
     */
    public function iChooseToDownloadADocumentVersionOfTheLPA(): void
    {
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, ''));

        $pdfService = $this->container->get(PdfService::class);

        $pdfStream = $pdfService->getLpaAsPdf($this->viewedLpa);

        $request = $this->apiFixtures->getLastRequest();
        Assert::assertStringContainsString('Mr Test Testable Testerson', $request->getBody()->getContents());
    }

    /**
     * @Then /^a PDF is downloaded$/
     */
    public function aPDFIsDownloaded(): void
    {
        // not used in this context
    }

    /**
     * @When /^I realise the LPA is incorrect$/
     */
    public function iRealiseTheLPAIsCorrect(): void
    {
        // not used in this context
    }

    /**
     * @Then /^I want to see an option to re-enter code$/
     */
    public function iWantToSeeAnOptionToReEnterCode(): void
    {
        // not used in this context
    }

    /**
     * @Then /^I want to see an option to check another LPA$/
     */
    public function iWantToSeeAnOptionToCheckAnotherLPA(): void
    {
        // not used in this context
    }
}
