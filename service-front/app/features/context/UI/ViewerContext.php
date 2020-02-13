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
 */
class ViewerContext implements Context
{
    use ViewerContextTrait;
    use BaseUiContextTrait;

    /**
     * @Given /^I have been given access to an LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnLPAViaShareCode() {
        $this->lpaSurname = 'Testerson';
        $this->lpaShareCode = '1111-1111-1111';
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
                'middlenames' =>'Testable',
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
     * @Given /^I access the viewer service$/
     */
    public function iAccessTheViewerService() {
        $this->ui->iAmOnHomepage();
        $this->ui->assertElementContainsText('a[role=button]', 'Start');
        $this->ui->clickLink('Start');
    }

    /**
     * @When /^I give a valid LPA share code$/
     */
    public function iGiveAValidLPAShareCode() {
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
                assertEquals($params['code'], str_replace('-', '', $this->lpaShareCode));
            });

        $this->ui->fillField('donor_surname', $this->lpaSurname);
        $this->ui->fillField('lpa_code', $this->lpaShareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I confirm the LPA is correct$/
     */
    public function iConfirmTheLPAIsCorrect() {
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
                assertEquals($params['code'], str_replace('-', '', $this->lpaShareCode));
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
        $this->ui->assertPageContainsText('This LPA is valid');
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

        $this->ui->clickLink('Download a copy of this page');
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



}