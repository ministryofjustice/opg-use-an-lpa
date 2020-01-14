<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Context;
use BehatTest\Context\ViewerContextTrait;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Class ViewerContext
 *
 * @property $lpaSurname
 * @property $lpaShareCode
 * @property $lpaData
 * @property $viewedLpa
 */
class ViewerContext implements Context, Psr11AwareContext
{
    use ViewerContextTrait;

    /** @var ContainerInterface */
    private $container;

    /** @var MockHandler */
    private $apiFixtures;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $this->container->get(MockHandler::class);
    }

    /**
     * @Given /^I have been given access to an LPA via share code$/
     */
    public function iHaveBeenGivenAccessToAnLPAViaShareCode()
    {
        $this->lpaShareCode = '1111-1111-1111';
        $this->lpaSurname = 'Testerson';
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
        // not used in this context
    }

    /**
     * @When /^I give a valid LPA share code$/
     */
    public function iGiveAValidLPAShareCode() {
        // not used in this context
    }

    /**
     * @When /^I confirm the LPA is correct$/
     */
    public function iConfirmTheLPAIsCorrect() {
        // not used in this context
    }

    /**
     * @Given /^I am viewing a valid LPA$/
     */
    public function iAmViewingAValidLPA()
    {
        $this->apiFixtures->post('/v1/viewer-codes/full')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'lpa' => $this->lpaData
            ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $body = json_decode($request->getBody()->getContents());

                assertEquals('111111111111', $body->code); // code gets hyphens removed
                assertEquals($this->lpaSurname, $body->name);
            });

        $lpaService = $this->container->get(LpaService::class);

        $this->viewedLpa = ($lpaService->getLpaByCode($this->lpaShareCode, $this->lpaSurname, LpaService::FULL))['lpa'];
    }

    /**
     * @When /^I choose to download a document version of the LPA$/
     */
    public function iChooseToDownloadADocumentVersionOfTheLPA()
    {
        $this->apiFixtures->post('/generate-pdf')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], ''))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                assertContains('Mr Test Testable Testerson', $request->getBody()->getContents());
            });

        $pdfService = $this->container->get(PdfService::class);

        $pdfStream = $pdfService->getLpaAsPdf($this->viewedLpa);
    }

    /**
     * @Then /^a PDF is downloaded$/
     */
    public function aPDFIsDownloaded()
    {
        // not used in this context
    }
}